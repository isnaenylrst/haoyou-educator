<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\SelfStudyCertificate;
use App\Models\User;
use App\Support\SelfStudyReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class SelfStudyCertificateController extends Controller
{
    public function store()
    {
        $res = SelfStudyReport::issue(auth()->user());

        return redirect()
            ->route('student.certificates.index', ['tab' => 'mandiri'])
            ->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function download(SelfStudyCertificate $certificate)
    {
        abort_unless($certificate->user_id === auth()->id(), 403);

        $pdf = Pdf::loadView('Siswa.belajar-mandiri.certificate', [
            'c'          => $certificate,
            'logo'       => $this->publicUri(config('selfstudy.logo')),
            'curriculum' => $this->signer(config('selfstudy.curriculum', [])),
            'owner'      => $this->signer(config('selfstudy.owner', [])),
        ])->setPaper('a4', 'landscape');

        $file = 'Sertifikat-Belajar-Mandiri-' . str_replace('/', '-', $certificate->certificate_number) . '.pdf';

        return $pdf->download($file);
    }

    /** Nama, jabatan, dan gambar tanda tangan satu penandatangan. */
    private function signer(array $cfg): array
    {
        $signature = null;

        // 1) tanda tangan dari profil akun (users.signature_path)
        if (! empty($cfg['user_id']) && ($user = User::find($cfg['user_id'])) && $user->signature_path) {
            foreach ([
                Storage::disk('public')->path($user->signature_path),
                storage_path('app/' . ltrim($user->signature_path, '/')),
                public_path($user->signature_path),
            ] as $candidate) {
                if (is_file($candidate)) {
                    $signature = $this->fileUri($candidate);
                    break;
                }
            }
        }

        // 2) cadangan: gambar di folder public/
        $signature ??= $this->publicUri($cfg['signature'] ?? null);

        return [
            'name'      => $cfg['name'] ?? '',
            'title'     => $cfg['title'] ?? '',
            'signature' => $signature,
        ];
    }

    private function publicUri(?string $relativePath): ?string
    {
        return $relativePath ? $this->fileUri(public_path($relativePath)) : null;
    }

    private function fileUri(string $fullPath): ?string
    {
        if (! is_file($fullPath)) {
            return null;
        }
        $mime = mime_content_type($fullPath) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
    }
}