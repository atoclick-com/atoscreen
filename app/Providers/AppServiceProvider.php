<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Ignore harmless PHP tempnam() fallback notices on shared hosting/cPanel
        set_error_handler(function ($errno, $errstr) {
            if (str_contains($errstr, 'tempnam()')) {
                return true;
            }
            return false;
        }, E_NOTICE | E_WARNING);

        // Ensure critical storage directories exist and are writable
        $dirs = [
            storage_path('framework/views'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('logs'),
            storage_path('app/public'),
            base_path('bootstrap/cache'),
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            if (is_dir($dir) && !is_writable($dir)) {
                @chmod($dir, 0777);
            }
        }
    }
}
