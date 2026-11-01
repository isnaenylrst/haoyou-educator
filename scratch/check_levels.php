<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ProgramLevel;
use App\Models\ProgramCategory;
use App\Models\Program;

echo "=== ProgramLevel Schema ===\n";
foreach (DB::select("SHOW COLUMNS FROM program_levels") as $c) {
    echo "{$c->Field} | {$c->Type} | Null: {$c->Null}\n";
}

echo "\n=== Levels by Category ===\n";
foreach (ProgramCategory::all() as $cat) {
    $lvls = ProgramLevel::where('category_id', $cat->id)->get();
    echo "Category {$cat->id} ({$cat->category_name}): " . $lvls->pluck('level_name')->implode(', ') . "\n";
}

