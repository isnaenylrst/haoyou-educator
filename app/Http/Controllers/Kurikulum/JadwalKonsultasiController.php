<?php

namespace App\Http\Controllers\Kurikulum;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class JadwalKonsultasiController extends Controller
{
    public function index()
    {
        $consultations = Consultation::with('teacher')
            ->orderByRaw("status = 'Belum Terjadwal' desc")
            ->orderBy('scheduled_at')
            ->get();

        $teachers = Teacher::where('status', 'Active')
            ->orderBy('name')
            ->get();

        $totalBelumTerjadwal = $consultations->where('status', 'Belum Terjadwal')->count();

        return view('kurikulum.jadwal-konsultasi', compact(
            'consultations',
            'teachers',
            'totalBelumTerjadwal'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'teacher_id'       => ['required', 'integer', 'exists:teachers,id'],
            'type'             => ['required', Rule::in(['LP_PPT', 'Direktur', 'Trial_Teaching'])],
            'scheduled_at'     => ['nullable', 'date'],
            'notify_whatsapp'  => ['nullable', 'boolean'],
        ]);

        try {
            Consultation::create([
                'teacher_id'       => $validated['teacher_id'],
                'type'             => $validated['type'],
                'scheduled_at'     => $validated['scheduled_at'] ?? null,
                'status'           => !empty($validated['scheduled_at']) ? 'Terjadwal' : 'Belum Terjadwal',
                'notify_whatsapp'  => $request->boolean('notify_whatsapp'),
                'created_by'       => Auth::id(),
            ]);
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal menyimpan jadwal konsultasi: ' . $e->getMessage());
        }

        return redirect()
            ->route('kurikulum.jadwal-konsultasi')
            ->with('success', 'Jadwal konsultasi berhasil dibuat.');
    }

    public function update(Request $request, Consultation $consultation)
    {
        $request->validate([
            'scheduled_at' => ['required', 'date'],
        ]);

        $consultation->update([
            'scheduled_at' => $request->scheduled_at,
            'status' => 'Terjadwal',
        ]);

        return back()->with('success', 'Jadwal konsultasi berhasil diperbarui.');
    }

    public function complete(Consultation $consultation)
    {
        $consultation->update(['status' => 'Selesai']);

        return back()->with('success', 'Konsultasi ditandai selesai.');
    }

    public function cancel(Consultation $consultation)
    {
        $consultation->update(['status' => 'Dibatalkan']);

        return back()->with('success', 'Konsultasi dibatalkan.');
    }

    public function destroy(Consultation $consultation)
    {
        $consultation->delete();

        return back()->with('success', 'Jadwal konsultasi dihapus.');
    }
}