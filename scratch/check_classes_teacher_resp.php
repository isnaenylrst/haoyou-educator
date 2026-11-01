<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$classes = DB::table('classes')->select('id', 'class_name', 'teacher_id', 'teacher_response', 'status')->get();
foreach ($classes as $c) {
    echo "Class {$c->id} | {$c->class_name} | teacher_id: {$c->teacher_id} | teacher_response: '{$c->teacher_response}' | status: {$c->status}\n";
}

