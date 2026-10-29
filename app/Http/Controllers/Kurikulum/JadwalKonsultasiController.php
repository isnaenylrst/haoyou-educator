<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Teacher;
use App\Models\TeacherMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class JadwalKonsultasiController extends Controller
{
    private const TYPES = ['LP_PPT', 'Direktur', 'Trial_Teaching'];
    private const TABS  = ['aktif', 'selesai', 'dibatalkan', 'semua'];

    private const MESSAGES = [
        'teacher_id.exists'           => 'Guru tidak ditemukan atau tidak aktif.',
        'scheduled_at.after_or_equal' => 'Tanggal konsultasi tidak boleh sebelum hari ini.',
        'scheduled_at.date'           => 'Format tanggal dan jam tidak valid.',
        'scheduled_at.required'       => 'Tanggal dan jam wajib diisi.',
        'notes.max'                   => 'Catatan maksimal 1000 karakter.',
    ];

    private const ATTRIBUTES = [
        'teacher_id'   => 'guru',
        'type'         => 'jenis konsultasi',
        'scheduled_at' => 'tanggal dan jam',
        'notes'        => 'catatan',
    ];

    // ------------------------------------------------------------------ LIST

    public function index(Request $request)
    {
        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'aktif';

        $consultations = Consultation::with(['teacher', 'teacherMaterial'])
            ->when($tab === 'aktif', fn ($q) => $q->active())
            ->when($tab === 'selesai', fn ($q) => $q->where('status', 'Selesai'))
            ->when($tab === 'dibatalkan', fn ($q) => $q->where('status', 'Dibatalkan'))
            ->orderByRaw("FIELD(status,'Belum Terjadwal','Terjadwal','Selesai','Dibatalkan')")
            ->orderBy('scheduled_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $counts = Consultation::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $teachers = Teacher::where('status', 'Active')->orderBy('name')->get();

        return view('kurikulum.jadwal-konsultasi', compact('consultations', 'teachers', 'counts', 'tab'));
    }

    // ------------------------------------------------------------------ BUAT

    public function store(Request $request)
    {
        $data = $request->validate([
            'teacher_id' => [
                'required', 'integer',
                Rule::exists('teachers', 'id')->where('status', 'Active'),
            ],
            'type'            => ['required', Rule::in(self::TYPES)],
            'scheduled_at'    => ['nullable', 'date', 'after_or_equal:today'],
            'notes'           => ['nullable', 'string', 'max:1000'],
            'notify_whatsapp' => ['nullable', 'boolean'],
        ], self::MESSAGES, self::ATTRIBUTES);

        // Konsultasi LP & PPT / Direktur otomatis tertaut ke pengajuan guru yang sedang berjalan,
        // sehingga tampil di tahapan progres sisi Guru.
        $submissionId = null;
        if (in_array($data['type'], ['LP_PPT', 'Direktur'], true)) {
            $submissionId = TeacherMaterial::where('teacher_id', $data['teacher_id'])
                ->where('status', '!=', 'Approved')
                ->latest()
                ->value('id');
        }

        // Konsultasi Direktur hanya setelah konsultasi Kurikulum untuk pengajuan itu selesai
        if ($data['type'] === 'Direktur' && $submissionId) {
            $kurikulumSelesai = Consultation::where('teacher_material_id', $submissionId)
                ->where('type', 'LP_PPT')
                ->where('status', 'Selesai')
                ->exists();

            if (!$kurikulumSelesai) {
                return back()->withInput()->with('error', 'Konsultasi Kurikulum untuk pengajuan guru ini belum selesai, jadi Konsultasi Direktur belum bisa dibuat.');
            }
        }

        // Cegah jadwal ganda yang masih berjalan
        $duplicate = Consultation::active()
            ->where('teacher_id', $data['teacher_id'])
            ->where('type', $data['type'])
            ->when($submissionId, fn ($q) => $q->where('teacher_material_id', $submissionId))
            ->exists();

        if ($duplicate) {
            return back()->withInput()->with('error', 'Guru ini sudah punya jadwal konsultasi jenis yang sama yang masih berjalan.');
        }

        try {
            Consultation::create([
                'teacher_id'          => $data['teacher_id'],
                'teacher_material_id' => $submissionId,
                'type'                => $data['type'],
                'scheduled_at'        => $data['scheduled_at'] ?? null,
                'status'              => !empty($data['scheduled_at']) ? 'Terjadwal' : 'Belum Terjadwal',
                'notes'               => $data['notes'] ?? null,
                'notify_whatsapp'     => $request->boolean('notify_whatsapp'),
                'created_by'          => Auth::id(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Gagal menyimpan jadwal konsultasi. Silakan coba lagi.');
        }

        return redirect()->route('kurikulum.jadwal-konsultasi')->with('success', 'Jadwal konsultasi berhasil dibuat.');
    }

    // ------------------------------------------------------------------ UBAH STATUS

    /** Jadwalkan / ganti jadwal. */
    public function update(Request $request, Consultation $consultation)
    {
        if (!in_array($consultation->status, ['Belum Terjadwal', 'Terjadwal'], true)) {
            return back()->with('error', 'Konsultasi yang sudah selesai atau dibatalkan tidak bisa dijadwalkan ulang.');
        }

        $data = $request->validate([
            'scheduled_at' => ['required', 'date', 'after_or_equal:today'],
            'notes'        => ['nullable', 'string', 'max:1000'],
        ], self::MESSAGES, self::ATTRIBUTES);

        $consultation->update([
            'scheduled_at' => $data['scheduled_at'],
            'notes'        => $data['notes'] ?? null,
            'status'       => 'Terjadwal',
        ]);

        return back()->with('success', 'Jadwal konsultasi berhasil disimpan.');
    }

    public function complete(Consultation $consultation)
    {
        if ($consultation->status !== 'Terjadwal') {
            return back()->with('error', 'Hanya konsultasi berstatus Terjadwal yang bisa ditandai selesai.');
        }

        $consultation->update(['status' => 'Selesai']);

        return back()->with('success', 'Konsultasi ditandai selesai.');
    }

    public function cancel(Consultation $consultation)
    {
        if (!in_array($consultation->status, ['Belum Terjadwal', 'Terjadwal'], true)) {
            return back()->with('error', 'Konsultasi ini sudah selesai atau sudah dibatalkan.');
        }

        $consultation->update(['status' => 'Dibatalkan']);

        return back()->with('success', 'Konsultasi dibatalkan. Guru dapat mengajukan ulang.');
    }

    public function destroy(Consultation $consultation)
    {
        $consultation->delete();

        return back()->with('success', 'Jadwal konsultasi dihapus.');
    }
}