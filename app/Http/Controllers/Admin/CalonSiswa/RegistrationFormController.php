<?php

namespace App\Http\Controllers\Admin\CalonSiswa;

use App\Http\Controllers\Controller;
use App\Models\ClassEnrollment;
use App\Models\Student;
use App\Services\RegistrationFormService;

class RegistrationFormController extends Controller
{
    public function download(
        Student $siswa,
        RegistrationFormService $service,
        ?ClassEnrollment $enrollment = null
    ) {
        $fileName = 'Formulir-' . str_replace(' ', '-', $siswa->name) . '.pdf';

        return response($service->generate($siswa, $enrollment), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }
}