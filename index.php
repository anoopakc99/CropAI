<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$storageDirectories = [
    __DIR__.'/storage/framework',
    __DIR__.'/storage/framework/sessions',
    __DIR__.'/storage/framework/views',
    __DIR__.'/storage/framework/cache',
    __DIR__.'/storage/framework/cache/data',
    __DIR__.'/storage/logs',
    __DIR__.'/bootstrap/cache',
];

foreach ($storageDirectories as $directory) {
    if (! is_dir($directory)) {
        @mkdir($directory, 0755, true);
    }

    if (is_dir($directory) && ! is_writable($directory)) {
        @chmod($directory, 0755);
    }
}

if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
