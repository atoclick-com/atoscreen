<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Self-healing environment for zero-config deployment
$baseDir = dirname(__DIR__);
$envPath = $baseDir . '/.env';

if (!file_exists($envPath) && file_exists($baseDir . '/.env.example')) {
    @copy($baseDir . '/.env.example', $envPath);
}

if (file_exists($envPath)) {
    $envContent = file_get_contents($envPath);
    if (!preg_match('/^APP_KEY=base64:[a-zA-Z0-9+\/=]{44}/m', $envContent)) {
        $generatedKey = 'base64:' . base64_encode(random_bytes(32));
        if (preg_match('/^APP_KEY=.*$/m', $envContent)) {
            $envContent = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=' . $generatedKey, $envContent);
        } else {
            $envContent .= "\nAPP_KEY=" . $generatedKey . "\n";
        }
        @file_put_contents($envPath, $envContent);
        putenv("APP_KEY={$generatedKey}");
        $_ENV['APP_KEY'] = $generatedKey;
        $_SERVER['APP_KEY'] = $generatedKey;
    }
}

$sqlitePath = $baseDir . '/database/database.sqlite';
if (!file_exists($sqlitePath) && is_dir($baseDir . '/database')) {
    @touch($sqlitePath);
    @chmod($sqlitePath, 0666);
}

return Application::configure(basePath: $baseDir)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
