<?php

// Check if default storage path is writable by PHP worker
$storageViews = storage_path('framework/views');
if (!is_dir($storageViews)) {
    @mkdir($storageViews, 0777, true);
}
@chmod($storageViews, 0777);

$isWritable = false;
$probe = $storageViews . '/.probe_' . uniqid();
if (@file_put_contents($probe, '1') !== false) {
    @unlink($probe);
    $isWritable = true;
}

$compiled = env('VIEW_COMPILED_PATH');

if (empty($compiled)) {
    if ($isWritable) {
        $compiled = realpath($storageViews) ?: $storageViews;
    } else {
        // Fallback to system temporary directory (guaranteed writable on cPanel/Linux)
        $fallback = rtrim(sys_get_temp_dir(), '/\\') . '/atoscreen_views';
        if (!is_dir($fallback)) {
            @mkdir($fallback, 0777, true);
        }
        @chmod($fallback, 0777);
        $compiled = $fallback;
    }
}

return [

    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    |
    | Most templating systems load templates from disk. Here you may specify
    | an array of paths that should be checked for your views. Of course
    | the usual Laravel view path has already been registered for you.
    |
    */

    'paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    |
    | This option determines where all the compiled Blade templates will be
    | stored for your application. Typically, this is within the storage
    | directory. However, as usual, you are free to change this value.
    |
    */

    'compiled' => $compiled,

];
