<?php

use App\Http\Controllers\InstallController;
use Illuminate\Support\Facades\Route;

// Server & Database Setup Wizard
Route::prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'index'])->name('index');
    Route::post('/test-db', [InstallController::class, 'testDb'])->name('test-db');
    Route::post('/setup', [InstallController::class, 'setup'])->name('setup');
    Route::get('/complete', [InstallController::class, 'complete'])->name('complete');
});

// Direct Storage Media Serving (guarantees images & videos load even if symlinks fail)
Route::get('/storage/{path}', function (string $path) {
    $cleanPath = str_replace(['..\\', '../', '..'], '', $path);
    $fullPath = storage_path('app/public/' . ltrim($cleanPath, '/'));

    if (!file_exists($fullPath) || !is_file($fullPath)) {
        abort(404);
    }

    $response = new \Symfony\Component\HttpFoundation\BinaryFileResponse($fullPath);
    $response->setAutoEtag();
    $response->headers->set('Cache-Control', 'public, max-age=604800');
    $response->headers->set('Access-Control-Allow-Origin', '*');

    return $response;
})->where('path', '.*');

// Single Page Application Fallback
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
