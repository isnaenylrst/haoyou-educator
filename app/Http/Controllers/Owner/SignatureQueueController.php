<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\ProgressReport;
use App\Models\SignatureHistory;
use App\Models\Submission;
use App\Notifications\AppNotification;
use App\Services\CertificateService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SignatureQueueController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'reports');

        // 1. Antrean Progress Report (Sudah di-ACC Kurikulum, belum TTD Owner)
        $reportsQuery = ProgressReport::with(['student', 'teacher', 'enrollment.class'])
            ->whereNotNull('reviewed_at')
            ->whereNull('owner_signed_at')
            ->latest('reviewed_at');
        $pendingReports = $reportsQuery->paginate(12, ['*'], 'reports_page')->withQueryString();

        // 2. Antrean Sertifikat (Sudah Approved / di-ACC Kurikulum, belum TTD Owner)
        $certsQuery = Certificate::with(['student', 'teacher', 'enrollment.class'])
            ->where('status', 'Approved')
            ->whereNull('owner_signed_at')
            ->latest('reviewed_at');
        $pendingCertificates = $certsQuery->paginate(12, ['*'], 'certs_page')->withQueryString();

        // 3. Antrean Pengajuan (Review Owner / sudah di-ACC Kurikulum)
        $submissionsQuery = Submission::with(['teacher', 'curriculum', 'admin'])
            ->where(function ($q) {
                $q->where('status', 'Review Owner')
                  ->orWhere(function ($sub) {
                      $sub->whereNotNull('curriculum_approved_at')
                          ->whereNull('owner_approved_at')
                          ->whereIn('status', ['Diajukan', 'Review Kurikulum', 'Review Admin', 'Review Owner']);
                  });
            })
            ->latest('date');
        $pendingSubmissions = $submissionsQuery->paginate(12, ['*'], 'submissions_page')->withQueryString();

        // 4. Riwayat TTD
        $historyQuery = SignatureHistory::with('signer')->latest('signed_at');
        $signatureHistories = $historyQuery->paginate(15, ['*'], 'history_page')->withQueryString();

        $ownerSignature = Auth::user()->signature_path;

        return view('owner.ttd', compact(
            'tab',
            'pendingReports',
            'pendingCertificates',
            'pendingSubmissions',
            'signatureHistories',
            'ownerSignature'
        ));
    }

    /**
     * TTD satuan Progress Report
     */
    public function signReport(Request $request, ProgressReport $report)
    {
        $user = Auth::user();

        if (empty($user->signature_path) || !Storage::disk('public')->exists($user->signature_path)) {
            return back()->with('error', 'Anda belum mengunggah tanda tangan digital. Silakan buat atau unggah tanda tangan terlebih dahulu di menu Profil & Tanda Tangan.');
        }

        if (empty($report->reviewed_at)) {
            return back()->with('error', 'Progress report ini belum di-ACC oleh pihak Kurikulum.');
        }

        DB::transaction(function () use ($user, $report) {
            $report->update([
                'owner_signed_by'      => $user->id,
                'owner_signed_at'      => now(),
                'owner_signature_path' => $user->signature_path,
                'status'               => 'Published',
                'published_at'         => $report->published_at ?? now(),
            ]);

                \App\Services\ProgressReportDocument::store($report->refresh());

            // Simpan riwayat penandatanganan
            SignatureHistory::create([
                'document_type'  => 'progress_report',
                'document_id'    => $report->id,
                'signer_id'      => $user->id,
                'signer_role'    => 'Owner',
                'signature_path' => $user->signature_path,
                'notes'          => "Penandatanganan Progress Report untuk siswa {$report->student?->name}",
                'signed_at'      => now(),
                'created_at'     => now(),
            ]);

            // Notifikasi ke Siswa
            if ($report->student?->user) {
                $report->student->user->notify(new AppNotification(
                    title: 'Progress Report Disahkan Owner',
                    message: "Progress Report Anda untuk kelas " . ($report->enrollment?->class?->class_name ?? '') . " telah ditandatangani dan disahkan secara resmi oleh Owner.",
                    url: url('/progress-report'),
                    type: 'progress_report',
                    icon: 'fa-certificate'
                ));
            }
        });

        return back()->with('success', "Progress report untuk siswa {$report->student?->name} berhasil ditandatangani dan PDF telah terbarui.");
    }

    /**
     * TTD massal Progress Report
     */
    public function signReportBulk(Request $request)
    {
        $user = Auth::user();

        if (empty($user->signature_path) || !Storage::disk('public')->exists($user->signature_path)) {
            return back()->with('error', 'Anda belum mengunggah tanda tangan digital. Silakan buat atau unggah tanda tangan terlebih dahulu di menu Profil & Tanda Tangan.');
        }

        $validated = $request->validate([
            'report_ids'   => ['required', 'array', 'min:1'],
            'report_ids.*' => ['integer', 'exists:progress_reports,id'],
        ], [
            'report_ids.required' => 'Pilih minimal satu progress report untuk ditandatangani.',
        ]);

        $reports = ProgressReport::whereIn('id', $validated['report_ids'])
            ->whereNotNull('reviewed_at')
            ->whereNull('owner_signed_at')
            ->get();

        if ($reports->isEmpty()) {
            return back()->with('error', 'Tidak ada progress report yang valid untuk ditandatangani.');
        }

        $count = 0;
        foreach ($reports as $report) {
            DB::transaction(function () use ($user, $report, &$count) {
                $report->update([
                    'owner_signed_by'      => $user->id,
                    'owner_signed_at'      => now(),
                    'owner_signature_path' => $user->signature_path,
                    'status'               => 'Published',
                    'published_at'         => $report->published_at ?? now(),
                ]);

                        \App\Services\ProgressReportDocument::store($report->refresh());

                SignatureHistory::create([
                    'document_type'  => 'progress_report',
                    'document_id'    => $report->id,
                    'signer_id'      => $user->id,
                    'signer_role'    => 'Owner',
                    'signature_path' => $user->signature_path,
                    'notes'          => "Penandatanganan massal Progress Report siswa {$report->student?->name}",
                    'signed_at'      => now(),
                    'created_at'     => now(),
                ]);

                $count++;
            });
        }

        return back()->with('success', "{$count} progress report berhasil ditandatangani massal oleh Owner.");
    }

    /**
     * TTD satuan Sertifikat
     */
    public function signCertificate(Request $request, Certificate $certificate, CertificateService $certService)
    {
        $user = Auth::user();

        if (empty($user->signature_path) || !Storage::disk('public')->exists($user->signature_path)) {
            return back()->with('error', 'Anda belum mengunggah tanda tangan digital. Silakan buat atau unggah tanda tangan terlebih dahulu di menu Profil & Tanda Tangan.');
        }

        DB::transaction(function () use ($user, $certificate, $certService) {
            $certificate->update([
                'owner_signed_by'      => $user->id,
                'owner_signed_at'      => now(),
                'owner_signature_path' => $user->signature_path,
            ]);

            // Regenerate PDF dengan TTD Owner
            try {
                if ($certificate->teacher_id && $certificate->enrollment_id) {
                    $e = (object) [
                        'student_name' => $certificate->student_name ?: $certificate->student?->name,
                        'class_name'   => $certificate->enrollment?->class?->class_name,
                    ];
                    $pdf = Pdf::loadView('guru.certificate-pdf', [
                        'e'              => $e,
                        'teacher'        => $certificate->teacher,
                        'ownerSignature' => $user->signature_path,
                        'cert'           => [
                            'start'  => $certificate->start_date ?? now(),
                            'end'    => $certificate->end_date ?? now(),
                            'score'  => $certificate->score,
                            'course' => $certificate->course_name,
                        ],
                        'number'         => $certificate->certificate_number,
                    ])->setPaper('a4', 'landscape');

                    $path = "certificates/certificate-{$certificate->id}.pdf";
                    Storage::disk('public')->put($path, $pdf->output());
                    $certificate->update(['file_path' => $path]);
                } else {
                    $certService->ensurePdf($certificate, force: true);
                }
            } catch (\Throwable $err) {
                report($err);
            }

            SignatureHistory::create([
                'document_type'  => 'certificate',
                'document_id'    => $certificate->id,
                'signer_id'      => $user->id,
                'signer_role'    => 'Owner',
                'signature_path' => $user->signature_path,
                'notes'          => "Penandatanganan Sertifikat {$certificate->course_name} untuk siswa {$certificate->student_name}",
                'signed_at'      => now(),
                'created_at'     => now(),
            ]);

            // Notifikasi ke Siswa
            if ($certificate->user) {
                $certificate->user->notify(new AppNotification(
                    title: 'Sertifikat Ditandatangani Owner',
                    message: "Sertifikat {$certificate->course_name} Anda telah resmi ditandatangani oleh Owner dan dapat diunduh sekarang.",
                    url: url('/sertifikat'),
                    type: 'sertifikat',
                    icon: 'fa-certificate'
                ));
            }
        });

        return back()->with('success', "Sertifikat untuk {$certificate->student_name} berhasil ditandatangani dan PDF telah terbarui.");
    }

    /**
     * TTD massal Sertifikat
     */
    public function signCertificateBulk(Request $request, CertificateService $certService)
    {
        $user = Auth::user();

        if (empty($user->signature_path) || !Storage::disk('public')->exists($user->signature_path)) {
            return back()->with('error', 'Anda belum mengunggah tanda tangan digital. Silakan buat atau unggah tanda tangan terlebih dahulu di menu Profil & Tanda Tangan.');
        }

        $validated = $request->validate([
            'certificate_ids'   => ['required', 'array', 'min:1'],
            'certificate_ids.*' => ['integer', 'exists:certificates,id'],
        ], [
            'certificate_ids.required' => 'Pilih minimal satu sertifikat untuk ditandatangani.',
        ]);

        $certificates = Certificate::whereIn('id', $validated['certificate_ids'])
            ->where('status', 'Approved')
            ->whereNull('owner_signed_at')
            ->get();

        if ($certificates->isEmpty()) {
            return back()->with('error', 'Tidak ada sertifikat yang valid untuk ditandatangani.');
        }

        $count = 0;
        foreach ($certificates as $cert) {
            DB::transaction(function () use ($user, $cert, $certService, &$count) {
                $cert->update([
                    'owner_signed_by'      => $user->id,
                    'owner_signed_at'      => now(),
                    'owner_signature_path' => $user->signature_path,
                ]);

                try {
                    if ($cert->teacher_id && $cert->enrollment_id) {
                        $e = (object) [
                            'student_name' => $cert->student_name ?: $cert->student?->name,
                            'class_name'   => $cert->enrollment?->class?->class_name,
                        ];
                        $pdf = Pdf::loadView('guru.certificate-pdf', [
                            'e'              => $e,
                            'teacher'        => $cert->teacher,
                            'ownerSignature' => $user->signature_path,
                            'cert'           => [
                                'start'  => $cert->start_date ?? now(),
                                'end'    => $cert->end_date ?? now(),
                                'score'  => $cert->score,
                                'course' => $cert->course_name,
                            ],
                            'number'         => $cert->certificate_number,
                        ])->setPaper('a4', 'landscape');

                        $path = "certificates/certificate-{$cert->id}.pdf";
                        Storage::disk('public')->put($path, $pdf->output());
                        $cert->update(['file_path' => $path]);
                    } else {
                        $certService->ensurePdf($cert, force: true);
                    }
                } catch (\Throwable $err) {
                    report($err);
                }

                SignatureHistory::create([
                    'document_type'  => 'certificate',
                    'document_id'    => $cert->id,
                    'signer_id'      => $user->id,
                    'signer_role'    => 'Owner',
                    'signature_path' => $user->signature_path,
                    'notes'          => "Penandatanganan massal Sertifikat {$cert->course_name} siswa {$cert->student_name}",
                    'signed_at'      => now(),
                    'created_at'     => now(),
                ]);

                $count++;
            });
        }

        return back()->with('success', "{$count} sertifikat berhasil ditandatangani massal oleh Owner.");
    }

    /**
     * TTD / Persetujuan Pengajuan (Reimburse & Bahan Ajar) oleh Owner
     */
    public function signSubmission(Request $request, Submission $submission)
    {
        $user = Auth::user();

        if (empty($user->signature_path) || !Storage::disk('public')->exists($user->signature_path)) {
            return back()->with('error', 'Anda belum mengunggah tanda tangan digital. Silakan buat atau unggah tanda tangan terlebih dahulu di menu Profil & Tanda Tangan.');
        }

        DB::transaction(function () use ($user, $submission) {
            $submission->update([
                'status'               => 'Disetujui',
                'owner_id'             => $user->id,
                'owner_approved_at'    => now(),
                'owner_signature_path' => $user->signature_path,
                'owner_note'           => 'Disetujui dan ditandatangani Owner melalui antrean TTD.',
            ]);

            SignatureHistory::create([
                'document_type'  => 'submission',
                'document_id'    => $submission->id,
                'signer_id'      => $user->id,
                'signer_role'    => 'Owner',
                'signature_path' => $user->signature_path,
                'notes'          => "Persetujuan dan TTD Pengajuan {$submission->item_name} sebesar {$submission->formatted_amount}",
                'signed_at'      => now(),
                'created_at'     => now(),
            ]);

            if ($submission->teacher?->user) {
                $submission->teacher->user->notify(new AppNotification(
                    title: "Pengajuan Disetujui & Ditandatangani Owner",
                    message: "Pengajuan {$submission->item_name} sebesar {$submission->formatted_amount} telah disetujui dan ditandatangani Owner.",
                    url: url('/guru/pengajuan'),
                    type: 'pengajuan',
                    icon: 'fa-circle-check'
                ));
            }
        });

        return back()->with('success', "Pengajuan {$submission->item_name} berhasil disetujui dan ditandatangani.");
    }
}

