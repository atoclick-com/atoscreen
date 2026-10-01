<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:install-reset', function () {
    $path = storage_path('installed');
    if (file_exists($path)) {
        @unlink($path);
        $this->info('Application installation lock removed. Setup wizard is now unlocked at /install.');
    } else {
        $this->comment('Application is not currently locked as installed.');
    }
})->purpose('Unlock the web setup wizard by removing storage/installed');

Artisan::command('app:install-mark', function () {
    $path = storage_path('installed');
    file_put_contents($path, json_encode([
        'installed_at' => now()->toIso8601String(),
        'marked_via' => 'artisan',
    ], JSON_PRETTY_PRINT));
    $this->info('Application marked as installed (storage/installed created).');
})->purpose('Mark the application as installed');
