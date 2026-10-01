<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Self-healing environment for zero-config deployment
$baseDir = dirname(__DIR__);
$envPath = $baseDir . '/.env';

// 1. Ensure .env exists
if (!file_exists($envPath) && file_exists($baseDir . '/.env.example')) {
    @copy($baseDir . '/.env.example', $envPath);
}

// 1b. Fix subdirectory routing: detect if deployed in a subdirectory (e.g. /v)
//     either from APP_URL, REQUEST_URI, or directory structure.
$subDir = '';
if (file_exists($envPath)) {
    $rawEnv = file_get_contents($envPath);
    if (preg_match('/^APP_URL\s*=\s*(.+)$/m', $rawEnv, $m)) {
        $configuredAppUrl = trim($m[1]);
        $subDir = rtrim(parse_url($configuredAppUrl, PHP_URL_PATH) ?? '', '/');
    }
}

// Fallback: detect from REQUEST_URI if APP_URL is not yet configured with /v
if (empty($subDir) && isset($_SERVER['REQUEST_URI']) && preg_match('#^/([a-zA-Z0-9_\-]+)(/|$)#', $_SERVER['REQUEST_URI'], $rm)) {
    if (basename($baseDir) === $rm[1]) {
        $subDir = '/' . $rm[1];
    }
}

// Also detect from filesystem directory if project is directly under a web root (e.g. public_html/v)
if (empty($subDir) && basename($baseDir) === 'v') {
    $subDir = '/v';
}

if (!empty($subDir) && $subDir !== '/') {
    // Set SCRIPT_NAME and PHP_SELF so Symfony's getBaseUrl() returns e.g. "/v"
    // and getPathInfo() correctly strips it from "/v/api/..." → "/api/..."
    $_SERVER['SCRIPT_NAME'] = $subDir . '/index.php';
    $_SERVER['PHP_SELF']    = $subDir . '/index.php';

    // Auto-heal APP_URL in .env if missing or lacking the subdirectory prefix
    if (file_exists($envPath)) {
        $rawEnv = file_get_contents($envPath);
        $serverHost = $_SERVER['HTTP_HOST'] ?? 'trotiluxe.ma';
        $serverProto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'https://';
        $correctAppUrl = $serverProto . $serverHost . $subDir;

        if (preg_match('/^APP_URL\s*=\s*(.+)$/m', $rawEnv, $m)) {
            $existingAppUrl = trim($m[1]);
            if (!str_ends_with($existingAppUrl, $subDir) || str_contains($existingAppUrl, 'localhost')) {
                $rawEnv = preg_replace('/^APP_URL\s*=.*$/m', "APP_URL={$correctAppUrl}", $rawEnv);
                @file_put_contents($envPath, $rawEnv);
                putenv("APP_URL={$correctAppUrl}");
                $_ENV['APP_URL'] = $correctAppUrl;
                $_SERVER['APP_URL'] = $correctAppUrl;
            }
        }
    }
}



// 2. Patch .env: APP_KEY, SESSION_DRIVER, CACHE_STORE — handles LF and CRLF
if (file_exists($envPath)) {
    $envContent = file_get_contents($envPath);
    $envChanged = false;

    // 2a. Auto-generate APP_KEY if missing/empty
    if (!preg_match('/^APP_KEY=base64:[a-zA-Z0-9+\/=]{44}/m', $envContent)) {
        $generatedKey = 'base64:' . base64_encode(random_bytes(32));
        if (preg_match('/^APP_KEY=.*$/m', $envContent)) {
            $envContent = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=' . $generatedKey, $envContent);
        } else {
            $envContent .= "\nAPP_KEY=" . $generatedKey . "\n";
        }
        putenv("APP_KEY={$generatedKey}");
        $_ENV['APP_KEY'] = $generatedKey;
        $_SERVER['APP_KEY'] = $generatedKey;
        $envChanged = true;
    }

    // 2b. Force SESSION_DRIVER=file (database driver fails on readonly cPanel SQLite)
    if (preg_match('/^SESSION_DRIVER\s*=\s*database\s*$/im', $envContent)) {
        $envContent = preg_replace('/^SESSION_DRIVER\s*=\s*database\s*$/im', 'SESSION_DRIVER=file', $envContent);
        $envChanged = true;
    }
    putenv('SESSION_DRIVER=file');
    $_ENV['SESSION_DRIVER'] = 'file';
    $_SERVER['SESSION_DRIVER'] = 'file';

    // 2c. Force CACHE_STORE=file (same reason)
    if (preg_match('/^CACHE_STORE\s*=\s*database\s*$/im', $envContent)) {
        $envContent = preg_replace('/^CACHE_STORE\s*=\s*database\s*$/im', 'CACHE_STORE=file', $envContent);
        $envChanged = true;
    }
    putenv('CACHE_STORE=file');
    $_ENV['CACHE_STORE'] = 'file';
    $_SERVER['CACHE_STORE'] = 'file';

    if ($envChanged) {
        @file_put_contents($envPath, $envContent);
    }
}

// 3. Ensure SQLite database and its directory exist with full write permissions
$dbDir = $baseDir . '/database';
if (!is_dir($dbDir)) {
    @mkdir($dbDir, 0777, true);
}
@chmod($dbDir, 0777);

$sqlitePath = $dbDir . '/database.sqlite';
if (!file_exists($sqlitePath) && is_dir($dbDir)) {
    @touch($sqlitePath);
}
if (file_exists($sqlitePath)) {
    @chmod($sqlitePath, 0666);
}

// 4. Ensure all framework storage folders exist with full permissions
$storageDirs = [
    $baseDir . '/storage',
    $baseDir . '/storage/app',
    $baseDir . '/storage/app/public',
    $baseDir . '/storage/framework',
    $baseDir . '/storage/framework/views',
    $baseDir . '/storage/framework/cache',
    $baseDir . '/storage/framework/cache/data',
    $baseDir . '/storage/framework/sessions',
    $baseDir . '/storage/framework/testing',
    $baseDir . '/storage/logs',
    $baseDir . '/bootstrap/cache',
];

foreach ($storageDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    @chmod($dir, 0777);
}

// 5. Test if storage/framework/views is truly writable by PHP worker
$viewsDir = $baseDir . '/storage/framework/views';
$probeFile = $viewsDir . '/.probe_' . uniqid();
$viewsWritable = false;
if (@file_put_contents($probeFile, '1') !== false) {
    @unlink($probeFile);
    $viewsWritable = true;
}

if (!$viewsWritable) {
    // Fallback to system temporary directory (always writable by web process)
    $fallbackViews = rtrim(sys_get_temp_dir(), '/\\') . '/atoscreen_views';
    if (!is_dir($fallbackViews)) {
        @mkdir($fallbackViews, 0777, true);
    }
    @chmod($fallbackViews, 0777);
    putenv("VIEW_COMPILED_PATH={$fallbackViews}");
    $_ENV['VIEW_COMPILED_PATH'] = $fallbackViews;
    $_SERVER['VIEW_COMPILED_PATH'] = $fallbackViews;
}

$app = Application::configure(basePath: $baseDir)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'install/*',
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\CheckInstallation::class,
        ]);
        $middleware->api(append: [
            \App\Http\Middleware\CheckInstallation::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Intercept any stray tempnam notices/exceptions and still render the app
        $exceptions->render(function (\ErrorException $e) {
            if (str_contains($e->getMessage(), 'tempnam()')) {
                return response()->view('app');
            }
        });
    })->create();

// Suppress harmless PHP tempnam() E_NOTICE on shared hosting/cPanel
$app->booted(function () use ($sqlitePath) {
    set_error_handler(function ($severity, $message, $file = '', $line = 0) {
        if (str_contains($message, 'tempnam()')) {
            return true; // suppress notice so Laravel doesn't throw ErrorException
        }
        return false;
    }, E_NOTICE | E_WARNING);

    // Auto-migrate on first run if database or essential tables are missing
    try {
        if (!\Illuminate\Support\Facades\Schema::hasTable('users') || !\Illuminate\Support\Facades\Schema::hasTable('personal_access_tokens')) {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        }

        // Ensure default administrator exists if users table is empty
        if (\Illuminate\Support\Facades\Schema::hasTable('users') && \App\Models\User::count() === 0) {
            \App\Models\User::create([
                'name' => 'Admin',
                'email' => 'admin@atofood.com',
                'password' => 'password123',
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);
        }
    } catch (\Throwable $e) {
        // Migration will be handled via setup wizard or artisan
    }
});

return $app;
