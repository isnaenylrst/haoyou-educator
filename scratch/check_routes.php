<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$routes = array_keys(Illuminate\Support\Facades\Route::getRoutes()->getRoutesByName());
$viewFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../resources/views'));

$missing = [];

foreach ($viewFiles as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') continue;
    $content = file_get_contents($file->getPathname());
    if (preg_match_all("/route\(\s*['\"]([a-zA-Z0-9_\.\-]+)['\"]/", $content, $matches)) {
        foreach ($matches[1] as $routeName) {
            if (!in_array($routeName, $routes)) {
                $relPath = str_replace(dirname(__DIR__) . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $missing[$routeName][] = $relPath;
            }
        }
    }
}

ksort($missing);
foreach ($missing as $name => $files) {
    echo "Missing route: [$name] in:\n  - " . implode("\n  - ", array_unique($files)) . "\n\n";
}
if (empty($missing)) {
    echo "No missing routes found!\n";
}

