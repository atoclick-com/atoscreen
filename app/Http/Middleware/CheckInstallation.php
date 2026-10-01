<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInstallation
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isInstalled = file_exists(storage_path('installed'));

        // Always allow system health checks, static assets, and favicon
        if ($request->is('up', 'build/*', 'storage/*', 'assets/*', 'favicon.ico')) {
            return $next($request);
        }

        // 1. If application is NOT yet installed:
        if (!$isInstalled) {
            // Allow installer routes, wizard routes, display screens, and installation API calls
            if ($request->is('install*', 'wizard*', 'v/*', 'display/*', 'api/installer*', 'api/v1/wizard*', 'api/v1/display*')) {
                return $next($request);
            }

            // If an API request comes in, return structured 503 JSON with installer URL
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'not_installed',
                    'message' => 'AtoScreen has not been installed yet. Please run the server setup wizard.',
                    'install_url' => url('/install'),
                ], 503);
            }

            // Redirect all normal web requests to the installer wizard
            return redirect()->to(url('/install'));
        }

        // 2. If application IS installed:
        // Protect installer from unauthorized re-runs, but allow viewing completion page
        if ($isInstalled && ($request->is('install') || ($request->is('install/*') && !$request->is('install/complete')))) {
            return redirect()->to(url('/'));
        }

        return $next($request);
    }
}
