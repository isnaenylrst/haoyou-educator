<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\ClassSession;
use App\Models\CurriculumConsultationSlot;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\ConsultationSlotGridService;
use App\Services\MeetingConsultationRuleService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConsultationScheduleController extends Controller
{
    /**
     * Tampilan utama Jadwal Konsultasi Guru:
     * - Grid slot available (Kurikulum & Owner)
     * - Form pengajuan konsultasi
     * - Riwayat pengajuan milik Guru login
     * - Fitur Ganti Hari/Jam (Reschedule)
     */
    public function index(Request $request)
    {
        $teacher = Auth::user()->teacher;
        abort_unless($teacher, 403, 'Profil pengajar tidak ditemukan.');

        $weekParam = $request->query('week');
        try {
            $startOfWeek = $weekParam ? Carbon::parse($weekParam)->startOfWeek(Carbon::MONDAY) : now()->startOfWeek(Carbon::MONDAY);
        } catch (\Throwable) {
            $startOfWeek = now()->startOfWeek(Carbon::MONDAY);
        }
        $endOfWeek = $startOfWeek->copy()->endOfWeek(Carbon::SUNDAY);

        // Ambil slot dalam minggu ini (hanya slot utama / parent, relasikan subBookings)
        $weekSlots = CurriculumConsultationSlot::with(['curriculumUser', 'teacher', 'subBookings.teacher'])
            ->whereNull('parent_slot_id')
            ->whereBetween('date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->where('status', '!=', 'Cancelled')
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        // Siapkan grid kalender
        $grid = ConsultationSlotGridService::buildWeeklyGrid(
            startOfWeek: $startOfWeek,
            slots: $weekSlots,
            viewerUserId: Auth::id(),
            viewerRole: 'Teacher'
        );

        // Filter slot available masa depan untuk dipilih di form pengajuan baru
        $availableSlots = CurriculumConsultationSlot::with(['curriculumUser', 'subBookings'])
            ->whereNull('parent_slot_id')
            ->where('status', 'Available')
            ->where(function ($q) {
                $q->whereDate('date', '>', today())
                  ->orWhere(function ($q2) {
                      $q2->whereDate('date', today())
                         ->whereTime('start_time', '>', now()->format('H:i:s'));
                  });
            })
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        // Riwayat Pengajuan Konsultasi oleh Guru ini
        $myBookings = CurriculumConsultationSlot::with(['curriculumUser', 'class'])
            ->where('booked_by_teacher_id', $teacher->id)
            ->latest('booked_at')
            ->paginate(10, ['*'], 'history_page');

        // Kelas yang diampu guru ini untuk pilihan konsultasi (opsional)
        $classes = ClassModel::where('teacher_id', $teacher->id)
            ->whereIn('status', ['Open', 'Running'])
            ->orderBy('class_name')
            ->get();

        // Catatan Pertemuan (Blok 4 Pertemuan dinamis dari class_sessions) untuk Guru ini (GURU 4e)
        $meetingRuleSvc = app(MeetingConsultationRuleService::class);
        $meetingRecords = collect();
        foreach ($classes as $cl) {
            $blocks = $meetingRuleSvc->getBlocksForClass($cl);
            $meetingRecords->push([
                'class'   => $cl,
                'teacher' => $teacher,
                'blocks'  => $blocks,
            ]);
        }

        return view('guru.jadwal-konsultasi', compact('grid', 'availableSlots', 'myBookings', 'classes', 'meetingRecords', 'teacher'));
    }

    /**
     * Guru mengajukan konsultasi (Booking slot)
     * Mendukung: ke Kurikulum saja, ke Owner saja, atau KEDUANYA (GURU 4b).
     * Transaksi atomik dengan pengecekan lock, slot lewat, dan double booking.
     */
    public function book(Request $request)
    {
        $data = $request->validate([
            'booking_type'        => ['nullable', 'in:single,both'],
            'slot_id'             => ['required_without:slot_curriculum_id', 'nullable', 'integer', 'exists:curriculum_consultation_slots,id'],
            'slot_curriculum_id'  => ['nullable', 'integer', 'exists:curriculum_consultation_slots,id'],
            'slot_owner_id'       => ['nullable', 'integer', 'exists:curriculum_consultation_slots,id'],
            'booked_start_time'   => ['nullable', 'date_format:H:i'],
            'booked_end_time'     => ['nullable', 'date_format:H:i', 'after:booked_start_time'],
            'topic'               => ['required', 'string', 'max:255'],
            'class_id'            => ['nullable', 'integer', 'exists:classes,id'],
            'booking_notes'       => ['nullable', 'string', 'max:1000'],
        ], [
            'topic.required' => 'Topik konsultasi wajib diisi.',
            'slot_id.required_without' => 'Pilih slot konsultasi yang tersedia.',
            'booked_end_time.after' => 'Jam selesai harus setelah jam mulai.',
        ]);

        $teacher = Auth::user()->teacher;
        abort_unless($teacher, 403);

        $bookingType = $data['booking_type'] ?? 'single';
        $slotIdsToBook = [];

        if ($bookingType === 'both') {
            if (empty($data['slot_curriculum_id']) || empty($data['slot_owner_id'])) {
                return back()->withInput()->with('error', 'Untuk mengajukan ke Kurikulum dan Owner sekaligus, pilih satu slot Kurikulum dan satu slot Owner.');
            }
            if ($data['slot_curriculum_id'] == $data['slot_owner_id']) {
                return back()->withInput()->with('error', 'Slot Kurikulum dan Owner tidak boleh slot yang sama.');
            }
            $slotIdsToBook = [(int) $data['slot_curriculum_id'], (int) $data['slot_owner_id']];
        } else {
            $slotId = $data['slot_id'] ?? ($data['slot_curriculum_id'] ?? $data['slot_owner_id']);
            if (!$slotId) {
                return back()->withInput()->with('error', 'Silakan pilih slot konsultasi yang tersedia.');
            }
            $slotIdsToBook = [(int) $slotId];
        }

        // Cek bentrok dengan jadwal mengajar (class_sessions) guru ini (GURU 4c)
        $conflictWarnings = [];
        $slotsInfo = CurriculumConsultationSlot::whereIn('id', $slotIdsToBook)->get();
        foreach ($slotsInfo as $chkSlot) {
            $sDate = $chkSlot->date->toDateString();
            $sStart = ($data['booked_start_time'] ?? substr($chkSlot->start_time, 0, 5)) . ':00';
            $sEnd = ($data['booked_end_time'] ?? substr($chkSlot->end_time, 0, 5)) . ':00';

            $teachingClash = ClassSession::where('teacher_id', $teacher->id)
                ->whereDate('session_date', $sDate)
                ->where('status', '!=', 'Cancelled')
                ->where('start_time', '<', $sEnd)
                ->where('end_time', '>', $sStart)
                ->with('class:id,class_name')
                ->first();

            if ($teachingClash) {
                $cName = $teachingClash->class?->class_name ?? 'Kelas Mengajar';
                $conflictWarnings[] = "Peringatan: Jadwal slot " . $chkSlot->date->translatedFormat('d M Y') . " ({$sStart}-{$sEnd}) bentrok dengan jadwal sesi mengajar kelas \"{$cName}\".";
            }
        }

        return DB::transaction(function () use ($slotIdsToBook, $data, $teacher, $conflictWarnings) {
            // Lock semua slot yang akan dipesan dalam urutan ID untuk mencegah deadlock
            sort($slotIdsToBook);
            $lockedSlots = CurriculumConsultationSlot::whereIn('id', $slotIdsToBook)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($slotIdsToBook as $sId) {
                $slot = $lockedSlots->get($sId);
                if (!$slot) {
                    return back()->withInput()->with('error', 'Slot konsultasi tidak ditemukan.');
                }
                if ($slot->is_past) {
                    return back()->withInput()->with('error', "Slot konsultasi tanggal {$slot->date->translatedFormat('d M Y')} sudah lewat batas waktunya.");
                }
                if ($slot->status !== 'Available') {
                    return back()->withInput()->with('error', "Maaf, slot konsultasi tanggal {$slot->date->translatedFormat('d M Y')} jam " . substr($slot->start_time, 0, 5) . " sudah penuh atau dipesan guru lain.");
                }

                // Cek ketersediaan jam parsial
                $reqStart = $data['booked_start_time'] ?? substr($slot->start_time, 0, 5);
                $reqEnd   = $data['booked_end_time'] ?? substr($slot->end_time, 0, 5);

                $slotStartMin = ((int) substr($slot->start_time, 0, 2) * 60) + (int) substr($slot->start_time, 3, 2);
                $slotEndMin   = ((int) substr($slot->end_time, 0, 2) * 60) + (int) substr($slot->end_time, 3, 2);
                $reqStartMin  = ((int) substr($reqStart, 0, 2) * 60) + (int) substr($reqStart, 3, 2);
                $reqEndMin    = ((int) substr($reqEnd, 0, 2) * 60) + (int) substr($reqEnd, 3, 2);

                if ($reqStartMin < $slotStartMin || $reqEndMin > $slotEndMin || $reqEndMin <= $reqStartMin) {
                    return back()->withInput()->with('error', "Jam pemesanan ({$reqStart}-{$reqEnd}) berada di luar jendela slot (" . substr($slot->start_time, 0, 5) . "-" . substr($slot->end_time, 0, 5) . ").");
                }

                // Cek overlap dengan sub-bookings lain di slot ini
                $existingSubOverlap = CurriculumConsultationSlot::where('parent_slot_id', $slot->id)
                    ->where('status', 'Booked')
                    ->lockForUpdate()
                    ->where(function ($q) use ($reqStart, $reqEnd) {
                        $q->where(function ($sub) use ($reqStart, $reqEnd) {
                            $sub->where('booked_start_time', '<', $reqEnd . ':00')
                                ->where('booked_end_time', '>', $reqStart . ':00');
                        });
                    })
                    ->exists();

                if ($existingSubOverlap) {
                    return back()->withInput()->with('error', "Jam {$reqStart}-{$reqEnd} pada slot tanggal {$slot->date->translatedFormat('d M Y')} sudah dipesan guru lain.");
                }
            }

            // Eksekusi booking masing-masing slot
            foreach ($slotIdsToBook as $sId) {
                $slot = $lockedSlots->get($sId);
                $reqStart = $data['booked_start_time'] ?? substr($slot->start_time, 0, 5);
                $reqEnd   = $data['booked_end_time'] ?? substr($slot->end_time, 0, 5);

                $slotStartMin = ((int) substr($slot->start_time, 0, 2) * 60) + (int) substr($slot->start_time, 3, 2);
                $slotEndMin   = ((int) substr($slot->end_time, 0, 2) * 60) + (int) substr($slot->end_time, 3, 2);
                $reqStartMin  = ((int) substr($reqStart, 0, 2) * 60) + (int) substr($reqStart, 3, 2);
                $reqEndMin    = ((int) substr($reqEnd, 0, 2) * 60) + (int) substr($reqEnd, 3, 2);

                $isWholeWindow = ($reqStartMin === $slotStartMin && $reqEndMin === $slotEndMin);

                if ($isWholeWindow && $slot->subBookings()->count() === 0) {
                    // Pesan penuh seluruh jendela slot utama (backward-compatible)
                    $slot->update([
                        'status'                 => 'Booked',
                        'booked_by_teacher_id'   => $teacher->id,
                        'booked_start_time'      => $reqStart . ':00',
                        'booked_end_time'        => $reqEnd . ':00',
                        'class_id'               => $data['class_id'] ?? null,
                        'topic'                  => trim($data['topic']),
                        'booking_notes'          => $data['booking_notes'] ?? null,
                        'booked_at'              => now(),
                        'teacher_booking_status' => 'Pending',
                    ]);
                } else {
                    // Pesan sebagian jam -> buat sub-slot anak (parent_slot_id = slot->id)
                    CurriculumConsultationSlot::create([
                        'parent_slot_id'         => $slot->id,
                        'curriculum_user_id'     => $slot->curriculum_user_id,
                        'owner_role'             => $slot->owner_role,
                        'date'                   => $slot->date,
                        'start_time'             => $reqStart . ':00',
                        'end_time'               => $reqEnd . ':00',
                        'booked_start_time'      => $reqStart . ':00',
                        'booked_end_time'        => $reqEnd . ':00',
                        'status'                 => 'Booked',
                        'booked_by_teacher_id'   => $teacher->id,
                        'class_id'               => $data['class_id'] ?? null,
                        'topic'                  => trim($data['topic']),
                        'booking_notes'          => $data['booking_notes'] ?? null,
                        'booked_at'              => now(),
                        'teacher_booking_status' => 'Pending',
                    ]);

                    // Periksa apakah seluruh sisa waktu slot parent sudah habis
                    $slot->refresh();
                    if ($slot->available_minutes <= 0) {
                        $slot->update(['status' => 'Booked']);
                    }
                }

                // Kirim notifikasi ke penanggung jawab slot
                $targetUser = $slot->curriculumUser;
                if (!$targetUser) {
                    if ($slot->effective_role === 'Owner') {
                        $ownerLevel = \App\Models\Level::where('nama_level', 'Owner')->first();
                        $targetUser = User::where('level_id', $ownerLevel?->id_level)->first();
                    } else {
                        $currLevel = \App\Models\Level::where('nama_level', 'Curriculum')->first();
                        $targetUser = User::where('level_id', $currLevel?->id_level)->first();
                    }
                }

                if ($targetUser) {
                    $targetRoleName = $slot->effective_role;
                    $targetUser->notify(new AppNotification(
                        title: "Pengajuan Konsultasi Baru dari {$teacher->name}",
                        message: "{$teacher->name} mengajukan konsultasi untuk tanggal {$slot->date->translatedFormat('d M Y')} jam {$reqStart}-{$reqEnd} dengan topik: \"{$data['topic']}\".",
                        url: $targetRoleName === 'Owner' ? url('/owner/konsultasi?tab=pengajuan') : url('/kurikulum/jadwal-konsultasi'),
                        type: 'konsultasi',
                        icon: 'fa-calendar-plus'
                    ));
                }
            }

            $successMsg = count($slotIdsToBook) > 1
                ? 'Dua pengajuan konsultasi (Kurikulum dan Owner) berhasil dikirim dalam satu transaksi dan menunggu konfirmasi masing-masing.'
                : 'Pengajuan konsultasi berhasil dikirim dan menunggu konfirmasi.';

            if (!empty($conflictWarnings)) {
                return back()->with('success', $successMsg)->with('warning', implode(' ', $conflictWarnings));
            }

            return back()->with('success', $successMsg);
        });
    }

    /**
     * Fitur Ganti Hari/Jam (Reschedule) oleh Guru
     */
    public function reschedule(Request $request, CurriculumConsultationSlot $slot)
    {
        $teacher = Auth::user()->teacher;
        abort_unless($teacher, 403);

        // Hanya boleh ganti jadwal untuk slot miliknya sendiri
        if ((int) $slot->booked_by_teacher_id !== (int) $teacher->id) {
            return back()->with('error', 'Anda tidak memiliki hak untuk mengubah pengajuan ini.');
        }

        // Hanya boleh ganti jika statusnya belum lewat dan belum selesai/batal
        if ($slot->is_past) {
            return back()->with('error', 'Konsultasi yang sudah lewat waktunya tidak bisa diganti jadwalnya.');
        }

        if (!in_array($slot->teacher_booking_status, ['Pending', 'Confirmed'])) {
            return back()->with('error', 'Pengajuan dengan status ini tidak dapat diganti jadwalnya.');
        }

        $data = $request->validate([
            'new_slot_id' => ['required', 'integer', 'exists:curriculum_consultation_slots,id', 'different:current_slot_id'],
            'reason'      => ['nullable', 'string', 'max:500'],
        ], [
            'new_slot_id.required'  => 'Pilih slot jadwal baru.',
            'new_slot_id.different' => 'Pilih slot yang berbeda dari jadwal saat ini.',
        ]);

        return DB::transaction(function () use ($slot, $data, $teacher) {
            // Lock kedua slot untuk integritas transaksi
            $oldSlot = CurriculumConsultationSlot::where('id', $slot->id)->lockForUpdate()->firstOrFail();
            $newSlot = CurriculumConsultationSlot::where('id', $data['new_slot_id'])->lockForUpdate()->firstOrFail();

            if ($newSlot->is_past) {
                return back()->with('error', 'Slot baru yang dipilih sudah lewat batas waktunya.');
            }

            if ($newSlot->status !== 'Available') {
                return back()->with('error', 'Slot baru yang dipilih sudah terisi atau tidak tersedia.');
            }

            // Catat riwayat perpindahan jadwal
            $history = $oldSlot->reschedule_history ?? [];
            $history[] = [
                'from_slot_id' => $oldSlot->id,
                'from_date'    => $oldSlot->date->toDateString(),
                'from_time'    => substr($oldSlot->start_time, 0, 5) . ' - ' . substr($oldSlot->end_time, 0, 5),
                'from_role'    => $oldSlot->effective_role,
                'to_slot_id'   => $newSlot->id,
                'to_date'      => $newSlot->date->toDateString(),
                'to_time'      => substr($newSlot->start_time, 0, 5) . ' - ' . substr($newSlot->end_time, 0, 5),
                'to_role'      => $newSlot->effective_role,
                'reason'       => $data['reason'] ?? 'Permintaan ganti jadwal oleh guru',
                'rescheduled_at' => now()->toDateTimeString(),
            ];

            // Pindahkan data ke new slot
            $newSlot->update([
                'status'                 => 'Booked',
                'booked_by_teacher_id'   => $teacher->id,
                'class_id'               => $oldSlot->class_id,
                'topic'                  => $oldSlot->topic,
                'booking_notes'          => $oldSlot->booking_notes,
                'booked_at'              => now(),
                'teacher_booking_status' => 'Pending', // kembali ke pending untuk persetujuan ulang
                'reschedule_history'     => $history,
            ]);

            // Kembalikan slot lama menjadi Available dan bersihkan pemesan
            $oldSlot->update([
                'status'                 => 'Available',
                'booked_by_teacher_id'   => null,
                'class_id'               => null,
                'topic'                  => null,
                'booking_notes'          => null,
                'booked_at'              => null,
                'teacher_booking_status' => null,
            ]);

            // Kirim notifikasi ke penanggung jawab slot baru
            $targetUser = $newSlot->curriculumUser;
            if (!$targetUser) {
                if ($newSlot->effective_role === 'Owner') {
                    $ownerLevel = \App\Models\Level::where('nama_level', 'Owner')->first();
                    $targetUser = User::where('level_id', $ownerLevel?->id_level)->first();
                } else {
                    $currLevel = \App\Models\Level::where('nama_level', 'Curriculum')->first();
                    $targetUser = User::where('level_id', $currLevel?->id_level)->first();
                }
            }

            if ($targetUser) {
                $targetRoleName = $newSlot->effective_role;
                $targetUser->notify(new AppNotification(
                    title: "Perubahan Jadwal Konsultasi ({$teacher->name})",
                    message: "{$teacher->name} mengubah jadwal konsultasi ke tanggal {$newSlot->date->translatedFormat('d M Y')} jam " . substr($newSlot->start_time, 0, 5) . ". Alasan: " . ($data['reason'] ?? '-'),
                    url: $targetRoleName === 'Owner' ? url('/owner/konsultasi?tab=pengajuan') : url('/kurikulum/jadwal-konsultasi'),
                    type: 'konsultasi',
                    icon: 'fa-arrows-rotate'
                ));
            }

            return back()->with('success', 'Jadwal konsultasi berhasil dipindahkan dan menunggu konfirmasi ulang.');
        });
    }

    /**
     * Guru membatalkan pengajuan konsultasi miliknya
     */
    public function cancel(CurriculumConsultationSlot $slot)
    {
        $teacher = Auth::user()->teacher;
        abort_unless($teacher, 403);

        if ((int) $slot->booked_by_teacher_id !== (int) $teacher->id) {
            return back()->with('error', 'Anda tidak memiliki hak untuk membatalkan pengajuan ini.');
        }

        if ($slot->is_past) {
            return back()->with('error', 'Konsultasi yang sudah lewat tidak bisa dibatalkan.');
        }

        DB::transaction(function () use ($slot) {
            $lockedSlot = CurriculumConsultationSlot::where('id', $slot->id)->lockForUpdate()->firstOrFail();

            if ($lockedSlot->parent_slot_id) {
                $parentId = $lockedSlot->parent_slot_id;
                $lockedSlot->delete();

                // Kembalikan status parent ke Available bila sisa waktu bertambah
                $parent = CurriculumConsultationSlot::where('id', $parentId)->lockForUpdate()->first();
                if ($parent && $parent->available_minutes > 0) {
                    $parent->update(['status' => 'Available']);
                }
            } else {
                $lockedSlot->update([
                    'status'                 => 'Available',
                    'booked_by_teacher_id'   => null,
                    'booked_start_time'      => null,
                    'booked_end_time'        => null,
                    'class_id'               => null,
                    'topic'                  => null,
                    'booking_notes'          => null,
                    'booked_at'              => null,
                    'teacher_booking_status' => null,
                ]);
            }
        });

        return back()->with('success', 'Pengajuan konsultasi berhasil dibatalkan. Slot kembali tersedia.');
    }
}
