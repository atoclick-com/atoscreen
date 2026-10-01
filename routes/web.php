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

// Single Page Application Fallback
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
