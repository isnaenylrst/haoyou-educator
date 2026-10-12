<?php

namespace App\Http\Controllers\Admin\CalonSiswa;

use App\Http\Controllers\Controller;
use App\Models\CandidateStudent;
use App\Models\FollowUp;
use App\Models\FollowUpTemplate;
use App\Models\CandidateStudentAvailableSchedule;
use App\Models\PrivatePackage;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
        $query = CandidateStudent::with(['latestFollowUp', 'program', 'privatePackage'])
            ->whereDoesntHave('student');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('program_id')) {
            if ($request->program_id === 'private') {
                $query->whereNotNull('private_package_id');
            } else {
                $query->where('program_id', $request->program_id);
            }
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
        | Daftar Program & Private Package untuk dropdown "Program Diminati"
        |--------------------------------------------------------------------------
        */
        $programs = Program::orderBy('program_name')->get(['id', 'program_name']);
        $privatePackages = PrivatePackage::where('is_active', true)
            ->orderBy('package_name')
            ->get(['id', 'package_name']);

        return view('admin.calon-siswa.index', compact(
            'candidateStudents',
            'totalLead',
            'trialScheduled',
            'followUpOverdue',
            'conversionRate',
            'programs',
            'privatePackages'
        ));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {

            /*
            |--------------------------------------------------------------------------
            | Simpan Candidate Student
            |--------------------------------------------------------------------------
            */

            $candidate = CandidateStudent::create([

                'name' => $data['name'],
                'gender' => $data['gender'],
                'birth_date' => $data['birth_date'],
                'phone' => $data['phone'],
                'parent_name' => $data['parent_name'] ?? null,
                'parent_phone' => $data['parent_phone'] ?? null,
                'address' => $data['address'] ?? null,
                'school' => $data['school'] ?? null,
                'source' => $data['source'],
                'allergy' => $data['allergy'] ?? null,
                'program_id' => $data['program_id'],
                'private_package_id' => $data['private_package_id'],
                'trial_date' => $data['trial_date'] ?? null,
                'trial_status' => 'Pending',
                'lead_status' => 'Warm',

            ]);

            /*
            |--------------------------------------------------------------------------
            | Simpan Available Schedule
            |--------------------------------------------------------------------------
            */

            $this->syncSchedules($candidate, request());

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
            ->route('admin.calon-siswa.index')
            ->with('success', 'Lead berhasil ditambahkan.');
    }

    public function edit(CandidateStudent $candidateStudent)
    {
        $candidateStudent->load(['availableSchedules', 'program', 'privatePackage']);

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
            'interest_type' => $candidateStudent->private_package_id ? 'Private' : 'Reguler',
            'program_id' => $candidateStudent->program_id,
            'program_detail' => $candidateStudent->program ? [
                'program_name' => $candidateStudent->program->program_name,
            ] : null,
            'private_package_id' => $candidateStudent->private_package_id,
            'private_package_detail' => $candidateStudent->privatePackage ? [
                'package_name' => $candidateStudent->privatePackage->package_name,
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
        $data = $this->validated($request, $candidateStudent);

        DB::transaction(function () use ($data, $candidateStudent, $request) {
            $candidateStudent->update([
                'name' => $data['name'],
                'gender' => $data['gender'],
                'birth_date' => $data['birth_date'],
                'phone' => $data['phone'],
                'parent_name' => $data['parent_name'] ?? null,
                'parent_phone' => $data['parent_phone'] ?? null,
                'address' => $data['address'] ?? null,
                'school' => $data['school'] ?? null,
                'source' => $data['source'],
                'allergy' => $data['allergy'] ?? null,
                'program_id' => $data['program_id'],
                'private_package_id' => $data['private_package_id'],
                'trial_date' => $data['trial_date'] ?? null,
                'trial_status' => $data['trial_status'],
                'lead_status' => $data['lead_status'],
            ]);

            $this->syncSchedules($candidateStudent, $request);
        });

        return redirect()
            ->route('admin.calon-siswa.index')
            ->with('success', 'Data calon siswa berhasil diperbarui.');
    }

    /**
     * Menghapus data calon siswa beserta relasi terkait.
     */
    public function destroy(CandidateStudent $candidateStudent)
    {
        if ($candidateStudent->student()->exists()) {
            return redirect()
                ->route('admin.calon-siswa.index')
                ->with('error', 'Lead ini sudah menjadi siswa aktif dan tidak bisa dihapus.');
        }

        DB::transaction(function () use ($candidateStudent) {
            $candidateStudent->delete();
        });

        return redirect()
            ->route('admin.calon-siswa.index')
            ->with('success', 'Data calon siswa berhasil dihapus.');
    }

    private function validated(Request $request, ?CandidateStudent $candidateStudent = null): array
    {
        $isPrivate = $request->input('interest_type') === 'Private';

        $rules = [
            'interest_type' => ['required', Rule::in(['Reguler', 'Private'])],
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

            'program_id' => [Rule::requiredIf(! $isPrivate), 'nullable', 'exists:programs,id'],
            'private_package_id' => [Rule::requiredIf($isPrivate), 'nullable', 'exists:private_packages,id'],

            'trial_date' => 'nullable|date',

            'day.*' => 'nullable|string',
            'start_time.*' => 'nullable',
            'end_time.*' => 'nullable',
        ];

        if ($candidateStudent) {
            $rules['trial_status'] = 'required|in:Pending,Completed,Cancelled';
            $rules['lead_status'] = 'required|in:Cold,Warm,Hot';
        }

        $data = $request->validate($rules, [
            'program_id.required_if' => 'Pilih program yang diminati.',
            'private_package_id.required_if' => 'Pilih paket private yang diminati.',
        ]);

        $data['program_id'] = $isPrivate ? null : $data['program_id'];
        $data['private_package_id'] = $isPrivate ? $data['private_package_id'] : null;

        return $data;
    }

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