<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Certificate;
use App\Models\ProgramLevel;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class StudentCertificateController extends Controller
{
    public function __construct(private CertificateService $service)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        // SESUAIKAN: pakai query yang sekarang Anda pakai untuk $documents
        // (sertifikat upload manual dari kurikulum, untuk alumni lama). Contoh:
        // $documents = Document::where('user_id', $user->id)->where('type', 'certificate')
        //     ->with('programLevel.program')->latest()->get();
        // Sertifikat upload manual (alumni lama)
        $documents = Document::where('user_id', $user->id)
            ->where('document_type', 'certificate') // cek nilai aslinya di DB
            ->with('programLevel.program')
            ->latest()
            ->get();

        $certificates = Certificate::where('user_id', $user->id)->get()->keyBy('program_level_id');

        $levels = $this->service->candidateLevels($user)->map(fn (ProgramLevel $level) => [
            'level'       => $level,
            'certificate' => $certificates->get($level->id),
            'check'       => $this->service->check($user, $level),
        ]);

    return view('Siswa.certificates.index', compact('documents', 'levels', 'certificates'));
    }

    public function store(Request $request)
    {
        $data  = $request->validate(['program_level_id' => ['required', 'integer']]);
        $user  = $request->user();
        $level = $this->service->candidateLevels($user)->firstWhere('id', (int) $data['program_level_id']);

        abort_if(! $level, 403, 'Level ini bukan milikmu.');

        try {
            $certificate = $this->service->generate($user, $level);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('student.certificates.index')
            ->with('success', 'Sertifikat ' . $certificate->level_name . ' berhasil dibuat.');
    }

    public function download(Request $request, Certificate $certificate)
    {
        abort_unless($certificate->user_id === $request->user()->id, 403);

        $path = $this->service->ensurePdf($certificate);

        return Storage::disk('public')->download($path, 'Sertifikat-' . $certificate->level_name . '.pdf');
    }

    /** Halaman publik tujuan QR code. */
    public function verify(string $code)
    {
        $certificate = Certificate::where('verification_code', $code)->firstOrFail();

        return view('certificates.verify', compact('certificate'));
    }
}
