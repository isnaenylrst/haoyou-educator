<?php

namespace App\Services;

use App\Models\ClassEnrollment;
use App\Models\Student;
use setasign\Fpdi\Fpdi;

class RegistrationFormPdfService
{
    /**
     * Buat PDF formulir pendaftaran terisi otomatis untuk siswa ini,
     * berdasarkan enrollment yang dipilih (default: enrollment aktif terbaru).
     */
    public function generate(Student $student, ?ClassEnrollment $enrollment = null): string
    {
        $student->loadMissing('candidateStudent.availableSchedules', 'currentLevel.category');

        $enrollment = $enrollment ?? $student->activeEnrollment()->with([
            'class.schedules',
            'class.programPackage.program',
            'programPackage.program',
            'privatePackage',
            'payments',
        ])->first();

        abort_if(! $enrollment, 404, 'Siswa ini belum punya program/kelas yang bisa dicetak formulirnya.');

        $isPrivate = ! is_null($enrollment->private_package_id);

        return $isPrivate
            ? $this->fillPrivateForm($student, $enrollment)
            : $this->fillRegularForm($student, $enrollment);
    }

    public function generateTerms(): string
    {
        $template = storage_path('app/form-templates/form-regular.pdf');

        $pdf = new Fpdi('P', 'pt', [612, 792]);
        $pdf->setSourceFile($template);
        $templatePage = $pdf->importPage(1);
        $pdf->AddPage();
        $pdf->useTemplate($templatePage);

        return $pdf->Output('S');
    }

    private function fillRegularForm(Student $student, ClassEnrollment $enrollment): string
    {
        $template = storage_path('app/form-templates/form-regular.pdf');

        $pdf = $this->newPdf($template);

        $cs = $student->candidateStudent;
        $package = $enrollment->programPackage;
        $latestPayment = $enrollment->payments->sortByDesc('payment_date')->first();

        // ---- Data Wali Murid ----
        $this->text($pdf, 206, 218.8, $cs?->parent_name);
        $this->text($pdf, 206, 234.6, $cs?->address);
        $this->text($pdf, 206, 250.4, $cs?->parent_phone);
        $this->text($pdf, 206, 266.5, $cs?->source);

        // ---- Data Siswa ----
        $this->text($pdf, 458, 218.8, $student->name);
        $this->text($pdf, 458, 234.6, optional($cs?->birth_date)->format('d/m/Y'));
        $this->text($pdf, 458, 250.4, $cs?->age ? $cs->age . ' tahun' : null);
        $this->text($pdf, 458, 266.5, $cs?->allergy);
        $this->text($pdf, 458, 282.3, $cs?->school);

        // ---- Data Diisi Oleh Admin ----
        $this->text($pdf, 456, 314.1, now()->format('d/m/Y'));
        $this->text($pdf, 206, 329.9, $this->levelLabel($student));

        $scheduleLines = $this->scheduleLines($enrollment, $student);
        $this->text($pdf, 76, 361.7, $scheduleLines[0] ?? null);
        $this->text($pdf, 76, 377.5, $scheduleLines[1] ?? null);

        // Pertemuan Pertama: tidak ditrack, dibiarkan kosong
        $this->text($pdf, 206, 409.3, $package?->package_name);

        // Biaya Pendaftaran & Biaya Aktivitas: tidak ditrack, dibiarkan kosong
        $this->text($pdf, 458, 377.5, $package ? 'Rp ' . number_format($package->price, 0, ',', '.') : null);
        // Total Pembayaran: tergantung biaya pendaftaran/aktivitas yang tidak ditrack, dibiarkan kosong

        // ---- Kotak Keterangan DP/LUNAS (kolom pertama) ----
        if ($latestPayment) {
            $this->text($pdf, 185, 485.3, 'Rp ' . number_format($latestPayment->amount_paid, 0, ',', '.'));
            $this->text($pdf, 185, 517.3, optional($latestPayment->payment_date)->format('d/m/Y'));
        }

        return $pdf->Output('S');
    }

    private function fillPrivateForm(Student $student, ClassEnrollment $enrollment): string
    {
        $template = storage_path('app/form-templates/form-private.pdf');

        $pdf = $this->newPdf($template);

        $cs = $student->candidateStudent;
        $package = $enrollment->privatePackage;
        $latestPayment = $enrollment->payments->sortByDesc('payment_date')->first();

        // ---- Data Wali Murid ----
        $this->text($pdf, 206, 209.8, $cs?->parent_name);
        $this->text($pdf, 206, 225.6, $cs?->address);
        $this->text($pdf, 206, 241.4, $cs?->parent_phone);
        $this->text($pdf, 206, 257.4, $cs?->source);

        // ---- Data Siswa ----
        $this->text($pdf, 458, 209.8, $student->name);
        $this->text($pdf, 458, 225.6, optional($cs?->birth_date)->format('d/m/Y'));
        $this->text($pdf, 458, 241.4, $cs?->age ? $cs->age . ' tahun' : null);
        $this->text($pdf, 458, 257.4, $cs?->allergy);
        $this->text($pdf, 458, 273.3, $cs?->school);

        // ---- Data Diisi Oleh Admin ----
        $this->text($pdf, 456, 305.1, now()->format('d/m/Y'));
        $this->text($pdf, 206, 320.9, $package?->package_name);

        $scheduleLines = $this->scheduleLines($enrollment, $student);
        $this->text($pdf, 210, 336.7, implode(', ', $scheduleLines));

        // Total pertemuan, Pertemuan Pertama, Expired Date, Materi: tidak ditrack

        $this->text($pdf, 458, 368.5, $package ? 'Rp ' . number_format($package->price, 0, ',', '.') : null);
        // Biaya Pendaftaran, Biaya Aktivitas, Total Pembayaran: tidak ditrack

        // ---- Kotak Keterangan DP / LUNAS (2 kolom terpisah) ----
        if ($latestPayment) {
            $isLunas = $latestPayment->remaining_bill <= 0;
            $x = $isLunas ? 285 : 185;

            $this->text($pdf, $x, 476.2, 'Rp ' . number_format($latestPayment->amount_paid, 0, ',', '.'));
            $this->text($pdf, $x, 508.4, optional($latestPayment->payment_date)->format('d/m/Y'));
        }

        return $pdf->Output('S');
    }

    private function newPdf(string $templatePath): Fpdi
    {
        $pdf = new Fpdi('P', 'pt', [612, 792]); // Letter, satuan point sesuai ekstraksi koordinat
        $pdf->setSourceFile($templatePath);

        // Halaman 1 (syarat & ketentuan) disalin apa adanya
        $tpl1 = $pdf->importPage(1);
        $pdf->AddPage();
        $pdf->useTemplate($tpl1);

        // Halaman 2 (yang berisi kolom Data Wali Murid/Siswa/Admin) — ini yang ditimpa
        $tpl2 = $pdf->importPage(2);
        $pdf->AddPage();
        $pdf->useTemplate($tpl2);

        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->SetTextColor(0, 0, 0);

        return $pdf;
    }

    /**
     * Tulis teks di halaman TERAKHIR pdf (halaman 2) pada koordinat (x, y)
     * dengan y diukur dari atas halaman, sama seperti hasil ekstraksi struktur.
     */
    private function text(Fpdi $pdf, float $x, float $y, ?string $value): void
    {
        if (! $value) {
            return; // biarkan kosong untuk diisi manual
        }

        $pdf->SetXY($x, $y - 9); // Text() FPDF pakai baseline; mundurkan sedikit dari garis "bottom"
        $pdf->Cell(0, 9, $value, 0, 0, 'L');
    }

    private function levelLabel(Student $student): ?string
    {
        if (! $student->currentLevel) {
            return null;
        }

        $category = $student->currentLevel->category?->category_name;

        return $category ? "{$category} — {$student->currentLevel->level_name}" : $student->currentLevel->level_name;
    }

    /**
     * Jadwal diambil dari jadwal kelas (kalau sudah ditempatkan), atau dari
     * jadwal preferensi calon siswa (kalau masih Waiting Class / kelas Private).
     */
    private function scheduleLines(ClassEnrollment $enrollment, Student $student): array
    {
        $fromClass = $enrollment->class?->schedules
            ->map(fn ($s) => substr($s->day, 0, 3) . ' ' . substr($s->start_time, 0, 5) . '–' . substr($s->end_time, 0, 5))
            ->values()
            ->all();

        if (! empty($fromClass)) {
            return $fromClass;
        }

        return $student->candidateStudent?->availableSchedules
            ->map(fn ($s) => substr($s->day, 0, 3) . ' ' . substr($s->start_time, 0, 5) . '–' . substr($s->end_time, 0, 5))
            ->values()
            ->all() ?? [];
    }
}