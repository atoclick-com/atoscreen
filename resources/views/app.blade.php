<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $appSettings = \App\Models\AppSetting::instance();
        $appName = $appSettings->app_name ?: 'AtoFood Signage';
        $businessName = $appSettings->business_name ?: 'Digital Display Management';
        $detectedBase = rtrim(request()->getBasePath() ?: (parse_url(config('app.url'), PHP_URL_PATH) ?? ''), '/');
        if (empty($detectedBase) && (request()->is('v') || request()->is('v/*') || str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/v'))) {
            $detectedBase = '/v';
        }
    @endphp
    <title>{{ $appName }} | {{ $businessName }}</title>
    <script>
        // App base path for subdirectory deployments (e.g. '/v' when at trotiluxe.ma/v)
        window.__APP_BASE__ = @json($detectedBase);
        window.__APP_SETTINGS__ = {
            appName: @json($appName),
            businessName: @json($businessName)
        };
        try {
            localStorage.setItem('atofood_app_name', @json($appName));
            localStorage.setItem('atofood_business_name', @json($businessName));
        } catch(e) {}
    </script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#09090b] text-[#f4f4f5] antialiased selection:bg-amber-500/30 selection:text-amber-200 min-h-screen overflow-x-hidden font-sans">
    <div id="app"></div>
</body>
</html>
