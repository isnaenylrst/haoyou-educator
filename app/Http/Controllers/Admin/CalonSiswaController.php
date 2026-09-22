<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CandidateStudent;
use App\Models\FollowUp;
use App\Models\FollowUpTemplate;
use App\Models\CandidateStudentAvailableSchedule;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CalonSiswaController extends Controller
{
    /**
     * Menampilkan halaman CRM Calon Siswa
     */
    public function index(Request $request) {

        // ID follow-up "terbaru" per candidate_student, khusus untuk yang followupable_type-nya CandidateStudent
        $latestFollowUpIds = DB::table('follow_ups as f1')
            ->where('f1.followupable_type', CandidateStudent::class)
            ->whereRaw('f1.id = (
                select f2.id from follow_ups f2
                where f2.followupable_id = f1.followupable_id
                and f2.followupable_type = f1.followupable_type
                order by f2.followup_date desc, f2.id desc
                limit 1
            )')
            ->pluck('id');

        /*
        |--------------------------------------------------------------------------
        | Query Data Calon Siswa
        |--------------------------------------------------------------------------
        */
        $query = CandidateStudent::with('latestFollowUp')
            ->whereDoesntHave('student');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Filter Program
        if ($request->filled('program_id')) {
            $query->where('program_id', $request->program_id);
        }

        // Filter Status Lead
        if ($request->filled('lead_status')) {
            $query->where('lead_status', $request->lead_status);
        }

        // Filter Status Trial
        if ($request->filled('trial_status')) {
            $query->where('trial_status', $request->trial_status);
        }

        // Filter Follow Up
        if ($request->filled('followup')) {

            if ($request->followup == 'today') {

                $query->whereHas('followUps', function ($q) use ($latestFollowUpIds) {
                    $q->whereIn('id', $latestFollowUpIds)
                    ->whereDate('next_followup', today());
                });

            } elseif ($request->followup == 'overdue') {

                $query->whereHas('followUps', function ($q) use ($latestFollowUpIds) {
                    $q->whereIn('id', $latestFollowUpIds)
                    ->whereDate('next_followup', '<', today());
                });

            }
        }

        $candidateStudents = $query
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Statistik Dashboard
        |--------------------------------------------------------------------------
        */

        // Total Lead
        $totalLead = CandidateStudent::whereDoesntHave('student')->count();

        // Trial Pending
        $trialScheduled = CandidateStudent::whereDoesntHave('student')
            ->where('trial_status', 'Pending')
            ->count();

        // Follow Up Overdue (pakai latestFollowUpIds yang sama, konsisten dengan filter di atas)
        $followUpOverdue = CandidateStudent::whereDoesntHave('student')
            ->whereHas('followUps', function ($q) use ($latestFollowUpIds) {
                $q->whereIn('id', $latestFollowUpIds)
                ->whereDate('next_followup', '<', today());
            })
            ->count();

        // Lead yang berhasil menjadi siswa (dihitung dari total keseluruhan, termasuk yang sudah konversi)
        $totalLeadKeseluruhan = CandidateStudent::count();
        $convertedStudent = CandidateStudent::has('student')->count();

        // Conversion Rate
        $conversionRate = $totalLeadKeseluruhan > 0
            ? round(($convertedStudent / $totalLeadKeseluruhan) * 100, 1)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Daftar Program untuk dropdown "Program Diminati"
        |--------------------------------------------------------------------------
        */
        $programs = Program::orderBy('program_name')->get(['id', 'program_name']);

        return view('admin.calonsiswa', compact(
            'candidateStudents',
            'totalLead',
            'trialScheduled',
            'followUpOverdue',
            'conversionRate',
            'programs'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'gender' => 'required|in:Male,Female',
            'birth_date' => 'required|date',
            'phone' => 'required|string|max:20',

            'parent_name' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|string|max:20',

            'address' => 'nullable|string',
            'school' => 'nullable|string|max:255',
            'source' => 'required|string|max:100',
            'allergy' => 'nullable|string',

            'program_id' => 'required|exists:programs,id',
            'trial_date' => 'nullable|date',

            'day.*' => 'nullable|string',
            'start_time.*' => 'nullable',
            'end_time.*' => 'nullable',
        ]);

        DB::transaction(function () use ($request) {

            /*
            |--------------------------------------------------------------------------
            | Simpan Candidate Student
            |--------------------------------------------------------------------------
            */

            $candidate = CandidateStudent::create([

                'name' => $request->name,
                'gender' => $request->gender,
                'birth_date' => $request->birth_date,
                'phone' => $request->phone,
                'parent_name' => $request->parent_name,
                'parent_phone' => $request->parent_phone,
                'address' => $request->address,
                'school' => $request->school,
                'source' => $request->source,
                'allergy' => $request->allergy,
                'program_id' => $request->program_id,
                'trial_date' => $request->trial_date,
                'trial_status' => 'Pending',
                'lead_status' => 'Warm',

            ]);

            /*
            |--------------------------------------------------------------------------
            | Simpan Available Schedule
            |--------------------------------------------------------------------------
            */

            if ($request->has('day')) {

                foreach ($request->day as $index => $day) {

                    if (
                        empty($day) ||
                        empty($request->start_time[$index]) ||
                        empty($request->end_time[$index])
                    ) {
                        continue;
                    }

                    CandidateStudentAvailableSchedule::create([
                        'candidate_student_id' => $candidate->id,
                        'day' => $day,
                        'start_time' => $request->start_time[$index],
                        'end_time' => $request->end_time[$index],
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Buat Follow Up Pertama
            |--------------------------------------------------------------------------
            */

            $defaultTemplate = FollowUpTemplate::where('template_name', 'Follow Up Lead Dingin')->first()
                ?? FollowUpTemplate::first();

            if (!$defaultTemplate) {
                abort(500, 'Template follow up default tidak ditemukan. Jalankan FollowUpTemplateSeeder terlebih dahulu.');
            }

            // Pakai relasi morphMany supaya followupable_id & followupable_type terisi otomatis
            $candidate->followUps()->create([
                'follow_up_template_id' => $defaultTemplate->id,
                'followup_date' => now(),
                'followup_method' => 'Manual',
                'note' => 'Lead baru dibuat.',
                'next_followup' => now()->addDays(3),
                'status' => 'Pending',
            ]);
        });

        return redirect()
            ->route('admin.calon-siswa')
            ->with('success', 'Lead berhasil ditambahkan.');
    }

    public function edit(CandidateStudent $candidateStudent)
    {
        $candidateStudent->load(['availableSchedules', 'program']);

        return response()->json([
            'id' => $candidateStudent->id,
            'name' => $candidateStudent->name,
            'gender' => $candidateStudent->gender,
            'birth_date' => $candidateStudent->birth_date->format('Y-m-d'),
            'phone' => $candidateStudent->phone,
            'parent_name' => $candidateStudent->parent_name,
            'parent_phone' => $candidateStudent->parent_phone,
            'address' => $candidateStudent->address,
            'school' => $candidateStudent->school,
            'source' => $candidateStudent->source,
            'allergy' => $candidateStudent->allergy,
            'program_id' => $candidateStudent->program_id,
            'program_detail' => $candidateStudent->program ? [
                'program_name' => $candidateStudent->program->program_name,
            ] : null,
            'trial_date' => optional($candidateStudent->trial_date)->format('Y-m-d'),
            'trial_status' => $candidateStudent->trial_status,
            'lead_status' => $candidateStudent->lead_status,
            'schedules' => $candidateStudent->availableSchedules->map(function ($s) {
                return [
                    'day' => $s->day,
                    'start_time' => $s->start_time,
                    'end_time' => $s->end_time,
                ];
            }),
        ]);
    }

    /**
     * Menyimpan perubahan dari form Edit (submit modal Edit).
     */
    public function update(Request $request, CandidateStudent $candidateStudent)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'gender' => 'required|in:Male,Female',
            'birth_date' => 'required|date',
            'phone' => 'required|string|max:20',

            'parent_name' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|string|max:20',

            'address' => 'nullable|string',
            'school' => 'nullable|string|max:255',
            'source' => 'required|string|max:100',
            'allergy' => 'nullable|string',

            'program_id' => 'required|exists:programs,id',
            'trial_date' => 'nullable|date',
            'trial_status' => 'required|in:Pending,Completed,Cancelled',
            'lead_status' => 'required|in:Cold,Warm,Hot',

            'day.*' => 'nullable|string',
            'start_time.*' => 'nullable',
            'end_time.*' => 'nullable',
        ]);

        DB::transaction(function () use ($request, $candidateStudent) {
            $candidateStudent->update([
                'name' => $request->name,
                'gender' => $request->gender,
                'birth_date' => $request->birth_date,
                'phone' => $request->phone,
                'parent_name' => $request->parent_name,
                'parent_phone' => $request->parent_phone,
                'address' => $request->address,
                'school' => $request->school,
                'source' => $request->source,
                'allergy' => $request->allergy,
                'program_id' => $request->program_id,
                'trial_date' => $request->trial_date,
                'trial_status' => $request->trial_status,
                'lead_status' => $request->lead_status,
            ]);

            $this->syncSchedules($candidateStudent, $request);
        });

        return redirect()
            ->route('admin.calon-siswa')
            ->with('success', 'Data calon siswa berhasil diperbarui.');
    }

    /**
     * Menghapus data calon siswa beserta relasi terkait.
     */
    public function destroy(CandidateStudent $candidateStudent)
    {
        if ($candidateStudent->student()->exists()) {
            return redirect()
                ->route('admin.calon-siswa')
                ->with('error', 'Lead ini sudah menjadi siswa aktif dan tidak bisa dihapus.');
        }

        DB::transaction(function () use ($candidateStudent) {
            $candidateStudent->delete();
        });

        return redirect()
            ->route('admin.calon-siswa')
            ->with('success', 'Data calon siswa berhasil dihapus.');
    }

    /**
     * Hapus jadwal tersedia lama lalu simpan ulang sesuai input form.
     * Dipakai bersama oleh store() dan update() supaya tidak duplikasi logika.
     */
    private function syncSchedules(CandidateStudent $candidate, Request $request): void
    {
        $candidate->availableSchedules()->delete();

        if (!$request->has('day')) {
            return;
        }

        foreach ($request->day as $index => $day) {
            if (
                empty($day) ||
                empty($request->start_time[$index]) ||
                empty($request->end_time[$index])
            ) {
                continue;
            }

            CandidateStudentAvailableSchedule::create([
                'candidate_student_id' => $candidate->id,
                'day' => $day,
                'start_time' => $request->start_time[$index],
                'end_time' => $request->end_time[$index],
            ]);
        }
    }
}