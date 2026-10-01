<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Screen;
use App\Models\ScreenSetting;
use App\Models\Slide;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PDO;
use PDOException;
use Throwable;

class InstallController extends Controller
{
    /**
     * Display the Setup Wizard index page.
     */
    public function index()
    {
        // 1. PHP Requirements Check
        $minPhp = '8.2.0';
        $currentPhp = PHP_VERSION;
        $phpOk = version_compare($currentPhp, $minPhp, '>=');

        // 2. Required PHP Extensions Check
        $requiredExtensions = [
            'pdo' => 'PDO Data Objects',
            'pdo_mysql' => 'PDO MySQL Driver',
            'mbstring' => 'Multibyte String',
            'openssl' => 'OpenSSL Security',
            'tokenizer' => 'Tokenizer Support',
            'xml' => 'XML Processing',
            'ctype' => 'Character Type Checking',
            'json' => 'JSON Parser',
            'curl' => 'cURL Client',
            'fileinfo' => 'File Information / MIME',
        ];

        $extensionsStatus = [];
        $allExtensionsOk = true;

        foreach ($requiredExtensions as $ext => $label) {
            $isLoaded = extension_loaded($ext);
            $extensionsStatus[$ext] = [
                'name' => $label,
                'status' => $isLoaded,
            ];
            // SQLite can substitute for pdo_mysql if using sqlite, so don't hard-fail on pdo_mysql alone
            if (!$isLoaded && $ext !== 'pdo_mysql') {
                $allExtensionsOk = false;
            }
        }

        // 3. Writable Directories Check
        $directories = [
            'storage' => storage_path(),
            'storage/framework' => storage_path('framework'),
            'storage/framework/views' => storage_path('framework/views'),
            'storage/framework/cache' => storage_path('framework/cache'),
            'storage/framework/sessions' => storage_path('framework/sessions'),
            'storage/logs' => storage_path('logs'),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ];

        $directoryStatus = [];
        $allDirectoriesOk = true;

        foreach ($directories as $label => $path) {
            if (!is_dir($path)) {
                @mkdir($path, 0777, true);
            }
            @chmod($path, 0777);

            // Test true writability by placing a probe file
            $probe = $path . '/.probe_' . uniqid();
            $writable = false;
            if (@file_put_contents($probe, '1') !== false) {
                @unlink($probe);
                $writable = true;
            }

            $directoryStatus[$label] = [
                'path' => $path,
                'writable' => $writable,
            ];

            if (!$writable) {
                $allDirectoriesOk = false;
            }
        }

        $allRequirementsMet = $phpOk && $allExtensionsOk && $allDirectoriesOk;

        // Suggested defaults
        $currentUrl = request()->root();
        $suggestedAppName = env('APP_NAME', 'AtoScreen');
        if ($suggestedAppName === 'Laravel') {
            $suggestedAppName = 'AtoScreen';
        }

        return view('installer.wizard', [
            'phpVersion' => $currentPhp,
            'minPhpVersion' => $minPhp,
            'phpOk' => $phpOk,
            'extensions' => $extensionsStatus,
            'directories' => $directoryStatus,
            'allRequirementsMet' => $allRequirementsMet,
            'suggestedUrl' => $currentUrl,
            'suggestedAppName' => $suggestedAppName,
            'dbHost' => env('DB_HOST', '127.0.0.1'),
            'dbPort' => env('DB_PORT', '3306'),
            'dbDatabase' => env('DB_DATABASE', 'atoscreen'),
            'dbUsername' => env('DB_USERNAME', 'root'),
        ]);
    }

    /**
     * Test the database connection before saving.
     */
    public function testDb(Request $request): JsonResponse
    {
        $driver = $request->input('db_connection', 'mysql');

        if ($driver === 'sqlite') {
            $sqlitePath = $request->input('db_database') ?: database_path('database.sqlite');
            if (!file_exists($sqlitePath)) {
                @touch($sqlitePath);
                @chmod($sqlitePath, 0666);
            }

            if (file_exists($sqlitePath) && (is_writable($sqlitePath) || is_writable(dirname($sqlitePath)))) {
                return response()->json([
                    'success' => true,
                    'message' => 'SQLite database file is accessible and writable at: ' . $sqlitePath,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'SQLite database file at [' . $sqlitePath . '] is not writable by the web server process.',
            ], 422);
        }

        // MySQL / MariaDB
        $host = $request->input('db_host', '127.0.0.1');
        $port = $request->input('db_port', '3306');
        $database = trim($request->input('db_database', ''));
        $username = trim($request->input('db_username', ''));
        $password = (string) $request->input('db_password', '');

        if (empty($database)) {
            return response()->json([
                'success' => false,
                'message' => 'Database Name is required.',
            ], 422);
        }

        if (empty($username)) {
            return response()->json([
                'success' => false,
                'message' => 'Database Username is required.',
            ], 422);
        }

        try {
            // Test direct connection
            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Connection successful! Connected to database '{$database}' on {$host}:{$port}.",
            ]);
        } catch (PDOException $e) {
            // Check if error is 'Unknown database' (code 1049) -> try to auto-create it
            if ($e->getCode() == 1049 || str_contains(strtolower($e->getMessage()), 'unknown database')) {
                try {
                    $rootDsn = "mysql:host={$host};port={$port};charset=utf8mb4";
                    $rootPdo = new PDO($rootDsn, $username, $password, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_TIMEOUT => 5,
                    ]);
                    $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

                    return response()->json([
                        'success' => true,
                        'message' => "Database '{$database}' was created and verified successfully on {$host}:{$port}!",
                    ]);
                } catch (Throwable $createEx) {
                    return response()->json([
                        'success' => false,
                        'message' => "Database '{$database}' does not exist on {$host}:{$port}, and automatic creation failed: " . $createEx->getMessage() . ". Please create the database in your cPanel / hosting control panel first.",
                    ], 422);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Execute full application installation and database setup.
     */
    public function setup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'app_name' => 'required|string|max:50',
            'app_url' => 'required|string',
            'db_connection' => 'required|in:mysql,sqlite',
            'db_host' => 'required_if:db_connection,mysql|nullable|string',
            'db_port' => 'required_if:db_connection,mysql|nullable|numeric',
            'db_database' => 'required|string',
            'db_username' => 'required_if:db_connection,mysql|nullable|string',
            'db_password' => 'nullable|string',
            'admin_name' => 'required|string|max:100',
            'admin_email' => 'required|email|max:150',
            'admin_password' => 'required|string|min:6',
            'seed_starter_content' => 'nullable|boolean',
        ]);

        $driver = $validated['db_connection'];
        $host = $validated['db_host'] ?? '127.0.0.1';
        $port = $validated['db_port'] ?? '3306';
        $database = $validated['db_database'];
        $username = $validated['db_username'] ?? '';
        $password = $validated['db_password'] ?? '';

        // 1. Verify DB connection prior to touching system state
        if ($driver === 'mysql') {
            try {
                $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
                new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5,
                ]);
            } catch (PDOException $e) {
                if ($e->getCode() == 1049 || str_contains(strtolower($e->getMessage()), 'unknown database')) {
                    try {
                        $rootDsn = "mysql:host={$host};port={$port};charset=utf8mb4";
                        $rootPdo = new PDO($rootDsn, $username, $password, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_TIMEOUT => 5,
                        ]);
                        $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    } catch (Throwable $createEx) {
                        return response()->json([
                            'success' => false,
                            'message' => "Database '{$database}' does not exist and could not be created: " . $createEx->getMessage(),
                        ], 422);
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Could not connect to MySQL database: ' . $e->getMessage(),
                    ], 422);
                }
            }
        } elseif ($driver === 'sqlite') {
            if (!file_exists($database)) {
                @touch($database);
                @chmod($database, 0666);
            }
        }

        try {
            // 2. Update .env File
            $this->updateEnvConfiguration([
                'APP_NAME' => '"' . str_replace('"', '', $validated['app_name']) . '"',
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false',
                'APP_URL' => rtrim($validated['app_url'], '/'),
                'DB_CONNECTION' => $driver,
                'DB_HOST' => $host,
                'DB_PORT' => $port,
                'DB_DATABASE' => $driver === 'sqlite' ? $database : $database,
                'DB_USERNAME' => $username,
                'DB_PASSWORD' => '"' . str_replace('"', '', $password) . '"',
                'SESSION_DRIVER' => 'database',
                'CACHE_STORE' => 'database',
            ]);

            // 3. Dynamically re-configure runtime database connection
            if ($driver === 'mysql') {
                Config::set('database.default', 'mysql');
                Config::set('database.connections.mysql.host', $host);
                Config::set('database.connections.mysql.port', $port);
                Config::set('database.connections.mysql.database', $database);
                Config::set('database.connections.mysql.username', $username);
                Config::set('database.connections.mysql.password', $password);
            } else {
                Config::set('database.default', 'sqlite');
                Config::set('database.connections.sqlite.database', $database);
            }

            DB::purge();
            DB::reconnect();

            // 4. Run database migrations
            Artisan::call('migrate', ['--force' => true]);

            // 5. Create or Update Administrator Account
            $adminUser = User::updateOrCreate(
                ['email' => $validated['admin_email']],
                [
                    'name' => $validated['admin_name'],
                    'password' => Hash::make($validated['admin_password']),
                    'role' => 'admin',
                    'email_verified_at' => now(),
                ]
            );

            // 6. Update or Create Global App Settings
            $appDomain = preg_replace('/^https?:\/\//i', '', rtrim($validated['app_url'], '/'));
            AppSetting::updateOrCreate(
                ['id' => 1],
                [
                    'app_name' => $validated['app_name'],
                    'business_name' => $validated['app_name'] . ' Venue',
                    'display_domain' => $appDomain,
                    'master_pin' => '1234',
                    'default_slide_duration' => 10,
                    'default_transition' => 'fade',
                    'default_orientation' => 'landscape',
                ]
            );

            // 7. Seed Starter Screen & Slide (if checked or if no screens exist)
            $seedStarter = $request->boolean('seed_starter_content', true);
            $screen = Screen::first();

            if (!$screen && $seedStarter) {
                $screen = Screen::create([
                    'id' => (string) Str::uuid(),
                    'user_id' => $adminUser->id,
                    'name' => 'Main Display - 1',
                    'short_code' => '1',
                    'last_ping_at' => now(),
                    'default_slide_duration' => 10,
                    'transition_effect' => 'fade',
                    'orientation' => 'landscape',
                    'resolution_hint' => '1920x1080',
                ]);

                ScreenSetting::create([
                    'screen_id' => $screen->id,
                    'logo_overlay_enabled' => true,
                    'logo_path' => null,
                    'logo_position' => 'top-right',
                    'accent_color' => '#f59e0b',
                    'ticker_enabled' => true,
                    'ticker_text' => '✨ Welcome to ' . $validated['app_name'] . '! Live Signage is now active • High-Speed Guest Wi-Fi available ✨',
                    'ticker_speed' => 25,
                    'clock_widget_enabled' => true,
                    'clock_format' => '24h',
                    'auto_refresh_interval' => 60,
                    'screen_pin' => '1234',
                ]);

                Slide::create([
                    'screen_id' => $screen->id,
                    'type' => 'html_promo',
                    'title' => 'Welcome Display Slide',
                    'display_order' => 1,
                    'active' => true,
                    'fit_mode' => 'cover',
                    'content' => [
                        'headline' => 'Welcome to ' . $validated['app_name'],
                        'description' => 'Your digital signage display is now configured and connected to the server.',
                        'badge' => 'LIVE DISPLAY',
                        'accent_color' => '#f59e0b',
                        'bg_gradient' => 'linear-gradient(135deg, #1c1917 0%, #0c0a09 100%)',
                    ],
                ]);
            }

            // 8. Storage symlink & Cache optimization
            try {
                Artisan::call('storage:link', ['--force' => true]);
            } catch (Throwable $e) {}

            try {
                Artisan::call('optimize:clear');
            } catch (Throwable $e) {}

            // 9. Write Lockfile storage/installed
            $installReceipt = [
                'installed_at' => now()->toIso8601String(),
                'version' => '1.0.0',
                'app_name' => $validated['app_name'],
                'app_url' => $validated['app_url'],
                'db_driver' => $driver,
                'db_host' => $host,
                'db_database' => $database,
                'admin_email' => $validated['admin_email'],
            ];

            file_put_contents(storage_path('installed'), json_encode($installReceipt, JSON_PRETTY_PRINT));

            // Store summary in session for the complete page
            session([
                'install_summary' => [
                    'app_name' => $validated['app_name'],
                    'app_url' => $validated['app_url'],
                    'admin_email' => $validated['admin_email'],
                    'admin_password' => $validated['admin_password'],
                    'db_driver' => $driver,
                    'db_database' => $database,
                    'screen_url' => $screen ? url('/v/' . ($screen->short_code ?: '1')) : url('/v/1'),
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Application setup completed successfully!',
                'redirect_url' => route('install.complete'),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Installation encountered an error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the Installation Complete celebration page.
     */
    public function complete()
    {
        $summary = session('install_summary');

        if (!$summary && file_exists(storage_path('installed'))) {
            $data = json_decode(file_get_contents(storage_path('installed')), true);
            $summary = [
                'app_name' => $data['app_name'] ?? 'AtoScreen',
                'app_url' => $data['app_url'] ?? url('/'),
                'admin_email' => $data['admin_email'] ?? 'admin@atofood.com',
                'admin_password' => '(Configured during setup)',
                'db_driver' => $data['db_driver'] ?? 'mysql',
                'db_database' => $data['db_database'] ?? 'atoscreen',
                'screen_url' => url('/v/1'),
            ];
        }

        return view('installer.complete', [
            'summary' => $summary,
            'loginUrl' => url('/login'),
            'dashboardUrl' => url('/'),
        ]);
    }

    /**
     * Helper to safely update key/value pairs in .env
     */
    protected function updateEnvConfiguration(array $values): void
    {
        $envPath = base_path('.env');
        $examplePath = base_path('.env.example');

        if (!file_exists($envPath) && file_exists($examplePath)) {
            @copy($examplePath, $envPath);
        }

        $content = file_exists($envPath) ? file_get_contents($envPath) : '';

        // Ensure APP_KEY exists
        if (!preg_match('/^APP_KEY=base64:[a-zA-Z0-9+\/=]{44}/m', $content)) {
            $key = 'base64:' . base64_encode(random_bytes(32));
            $values['APP_KEY'] = $key;
        }

        foreach ($values as $key => $value) {
            $pattern = "/^{$key}=.*$/m";
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, "{$key}={$value}", $content);
            } else {
                $content .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $content);
    }
}
