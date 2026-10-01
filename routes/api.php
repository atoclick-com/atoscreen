<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DisplayController;
use App\Http\Controllers\Api\ScreenController;
use App\Http\Controllers\Api\ScreenSettingController;
use App\Http\Controllers\Api\SlideController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public TV Display Routes (No Authentication)
|--------------------------------------------------------------------------
| High performance endpoints used by kiosk browsers running on Smart TVs.
*/
Route::prefix('v1/display')->group(function () {
    Route::get('/{uuid}/playlist', [DisplayController::class, 'playlist']);
    Route::post('/{uuid}/ping', [DisplayController::class, 'ping']);
});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::prefix('v1/auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

/*
|--------------------------------------------------------------------------
| Protected Admin Routes (Sanctum)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Current user shortcut
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Screens Management
    Route::apiResource('screens', ScreenController::class);
    Route::post('/screens/{id}/regenerate-url', [ScreenController::class, 'regenerateUrl']);
    Route::post('/screens/{id}/reset', [ScreenController::class, 'reset']);
    Route::get('/screens/{id}/analytics', [ScreenController::class, 'analytics']);

    // Slides Management
    Route::get('/screens/{screen}/slides', [SlideController::class, 'index']);
    Route::post('/screens/{screen}/slides', [SlideController::class, 'store']);
    Route::post('/screens/{screen}/slides/batch-upload', [SlideController::class, 'batchUpload']);
    Route::post('/screens/{screen}/slides/reorder', [SlideController::class, 'reorder']);
    Route::post('/screens/{screen}/slides/batch-fit-mode', [SlideController::class, 'batchFitMode']);
    Route::put('/slides/{id}', [SlideController::class, 'update']);
    Route::post('/slides/{id}/toggle-active', [SlideController::class, 'toggleActive']);
    Route::delete('/slides/{id}', [SlideController::class, 'destroy']);

    // Screen Settings & Branding
    Route::get('/screens/{screen}/settings', [ScreenSettingController::class, 'show']);
    Route::post('/screens/{screen}/settings', [ScreenSettingController::class, 'update']);
    Route::post('/screens/{screen}/settings/logo', [ScreenSettingController::class, 'uploadLogo']);
    Route::delete('/screens/{screen}/settings/logo', [ScreenSettingController::class, 'removeLogo']);

    // Application & System Settings
    Route::get('/app-settings', [\App\Http\Controllers\Api\AppSettingController::class, 'show']);
    Route::post('/app-settings', [\App\Http\Controllers\Api\AppSettingController::class, 'updateSettings']);
    Route::post('/app-settings/logo', [\App\Http\Controllers\Api\AppSettingController::class, 'uploadLogo']);
    Route::delete('/app-settings/logo', [\App\Http\Controllers\Api\AppSettingController::class, 'removeLogo']);

    // User Access Information & Password
    Route::post('/user/profile', [\App\Http\Controllers\Api\AppSettingController::class, 'updateProfile']);
    Route::post('/user/password', [\App\Http\Controllers\Api\AppSettingController::class, 'updatePassword']);
});

/*
|--------------------------------------------------------------------------
| Setup Wizard Endpoints (Installation & Initial Setup)
|--------------------------------------------------------------------------
*/
Route::prefix('v1/wizard')->group(function () {
    Route::get('/initial-data', [\App\Http\Controllers\Api\SetupWizardController::class, 'initialData']);
    Route::post('/complete', [\App\Http\Controllers\Api\SetupWizardController::class, 'complete']);
});

