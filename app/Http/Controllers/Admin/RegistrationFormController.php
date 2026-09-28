<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\RegistrationFormPdfService;

class RegistrationFormController extends Controller
{
    public function terms(RegistrationFormPdfService $service)
    {
        return response($service->generateTerms(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Syarat-Ketentuan-Pendaftaran.pdf"',
        ]);
    }

    public function download(Student $siswa, RegistrationFormPdfService $service)
    {
        $pdfContent = $service->generate($siswa);

        $fileName = 'Formulir-' . str_replace(' ', '-', $siswa->name) . '.pdf';

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }
}