<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ClassModel;
use App\Models\ClassLearningMaterial;
use App\Models\ReportCompetency;

echo "ClassLearningMaterial total count: " . ClassLearningMaterial::count() . "\n";
echo "ReportCompetency total count: " . ReportCompetency::count() . "\n";

foreach (ClassModel::all() as $c) {
    $clm = ClassLearningMaterial::where('class_id', $c->id)->count();
    $rc = $c->level_id ? ReportCompetency::forLevel($c->level_id)->count() : 0;
    echo "Class {$c->id} ({$c->class_name}) | level_id: " . ($c->level_id ?? 'NULL') . " | CLM count: {$clm} | RC count: {$rc}\n";
}

