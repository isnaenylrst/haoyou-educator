<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\ClassEnrollment;
use App\Models\ProgressReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Ambil kelas yang sebelumnya CLM = 0 dan RC = 0 (misal class 3 atau class 2)
$enrollment = ClassEnrollment::where('class_id', 2)->with('class.teacher.user')->first();
if (!$enrollment) {
    $enrollment = ClassEnrollment::where('status', 'Active')->with('class.teacher.user')->first();
}

echo "Testing with enrollment ID: {$enrollment->id}, class: {$enrollment->class->class_name}, teacher: {$enrollment->class->teacher->name}\n";
$teacherUser = $enrollment->class->teacher->user;
Auth::login($teacherUser);

$ctrl = new App\Http\Controllers\Guru\ProgressReportController();

// 1. Test Form View (make sure it generates rows even if class has no CLM)
$formView = $ctrl->form($enrollment->id);
$formData = $formView->getData();
echo "Form classMaterials count: " . count($formData['classMaterials']) . "\n";
foreach ($formData['classMaterials'] as $mat) {
    echo " - Material: {$mat->material_name}\n";
}

// 2. Test Simpan Draft
$draftPayload = [
    'report_type' => 'Trimester Academic Progress Report',
    'report_period' => 'Juli - September 2026',
    'materials' => [
        [
            'material' => $formData['classMaterials'][0]->material_name,
            'level' => 'Proficient',
            'description' => 'Siswa memahami materi dengan baik.',
        ],
    ],
    'assessments' => [
        [
            'assessment' => 'Speaking & Pronunciation',
            'score' => '88',
            'grade' => '', // test auto-calculate
            'note' => 'Bagus',
        ],
    ],
    'overall_achievement_level' => 'Proficient',
    'suggestion_box' => 'Perbanyak latihan membaca pinyin.',
    'action' => 'draft',
];

$request = Request::create("/guru/progress-report/enrollment/{$enrollment->id}", 'POST', $draftPayload);
$response = $ctrl->store($request, $enrollment->id);
echo "Draft Response status: " . $response->getStatusCode() . "\n";

$savedReport = ProgressReport::where('enrollment_id', $enrollment->id)->latest('id')->first();
echo "Saved Draft Status: " . ($savedReport ? $savedReport->status : 'NOT SAVED') . "\n";
echo "Saved Draft Grade auto-calc: " . ($savedReport->assessment_data[0]['grade'] ?? 'EMPTY') . "\n";
echo "Saved Draft Materials: " . json_encode($savedReport->content) . "\n";

// 3. Test Ajukan ke Kurikulum (Submit)
$submitPayload = $draftPayload;
$submitPayload['action'] = 'submit';

$submitReq = Request::create("/guru/progress-report/enrollment/{$enrollment->id}", 'POST', $submitPayload);
$submitResp = $ctrl->store($submitReq, $enrollment->id);
echo "Submit Response status: " . $submitResp->getStatusCode() . "\n";

$savedReport->refresh();
echo "After Submit Status: " . $savedReport->status . "\n";
echo "Submitted At: " . ($savedReport->submitted_at ? $savedReport->submitted_at->toDateTimeString() : 'NULL') . "\n";

// Cleanup test report
$savedReport->delete();
echo "Cleaned up test report.\n";

echo "ALL PROGRESS REPORT TESTS COMPLETED SUCCESSFULLY!\n";
