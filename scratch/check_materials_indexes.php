<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (DB::select("SHOW INDEX FROM materials") as $idx) {
    echo "{$idx->Key_name} | {$idx->Column_name} | Non_unique: {$idx->Non_unique}\n";
}

