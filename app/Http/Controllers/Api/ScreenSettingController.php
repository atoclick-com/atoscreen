<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Screen;
use App\Models\ScreenSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ScreenSettingController extends Controller
{
    public function show(string $screenId): JsonResponse
    {
        $screen = Screen::findOrFail($screenId);
        $settings = $screen->settings ?: ScreenSetting::create([
            'screen_id' => $screen->id,
            'logo_overlay_enabled' => false,
            'logo_position' => 'top-right',
            'accent_color' => '#3b82f6',
            'ticker_enabled' => false,
            'ticker_speed' => 25,
            'clock_widget_enabled' => true,
            'clock_format' => '24h',
            'weather_widget_enabled' => false,
            'offline_fallback' => 'cached_playlist',
            'auto_refresh_interval' => 60,
        ]);

        return response()->json([
            'settings' => $settings,
        ]);
    }

    public function update(Request $request, string $screenId): JsonResponse
    {
        $screen = Screen::findOrFail($screenId);

        $validated = $request->validate([
            'logo_overlay_enabled' => 'sometimes|boolean',
            'logo_position' => 'sometimes|string|in:top-left,top-center,top-right,center-left,center,center-right,bottom-left,bottom-center,bottom-right',
            'accent_color' => 'sometimes|string|max:25',
            'audio_enabled' => 'sometimes|boolean',
            'audio_volume' => 'sometimes|integer|min:0|max:100',
            'aspect_ratio_mode' => 'sometimes|string|in:ambient_blur,contain,cover,stretch',
            'ticker_enabled' => 'sometimes|boolean',
            'ticker_text' => 'nullable|string|max:1000',
            'ticker_speed' => 'sometimes|integer|min:5|max:120',
            'clock_widget_enabled' => 'sometimes|boolean',
            'clock_format' => 'sometimes|string|in:12h,24h',
            'weather_widget_enabled' => 'sometimes|boolean',
            'weather_city' => 'nullable|string|max:100',
            'offline_fallback' => 'sometimes|string|in:cached_playlist,default_closed',
            'auto_refresh_interval' => 'sometimes|nullable|integer|min:5|max:3600',
            'operating_hours_enabled' => 'sometimes|boolean',
            'opening_time' => 'nullable|string|max:10',
            'closing_time' => 'nullable|string|max:10',
            'instagram_handle' => 'nullable|string|max:100',
            'screen_pin' => 'nullable|string|max:8',
            'apply_to_all_slides' => 'nullable|boolean',
        ]);

        $settings = $screen->settings ?: new ScreenSetting(['screen_id' => $screen->id]);
        $settings->fill($validated);
        $settings->save();

        if (!empty($validated['aspect_ratio_mode'])) {
            $screen->slides()->update(['fit_mode' => $validated['aspect_ratio_mode']]);
        }
        $screen->touch();

        return response()->json([
            'message' => 'Settings saved successfully',
            'settings' => $settings->fresh(),
        ]);
    }

    public function uploadLogo(Request $request, string $screenId): JsonResponse
    {
        $screen = Screen::findOrFail($screenId);

        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,webp,svg|max:10240', // 10MB max
        ]);

        $settings = $screen->settings ?: ScreenSetting::create(['screen_id' => $screen->id]);

        // Delete old logo file if exists
        if ($settings->logo_path && !str_starts_with($settings->logo_path, 'http')) {
            Storage::disk('public')->delete($settings->logo_path);
        }

        $path = $request->file('logo')->store("screens/{$screen->id}/branding", 'public');

        $settings->update([
            'logo_path' => $path,
            'logo_overlay_enabled' => true,
        ]);

        return response()->json([
            'message' => 'Logo uploaded and enabled',
            'settings' => $settings->fresh(),
            'logo_url' => $settings->logo_url,
        ]);
    }

    public function removeLogo(string $screenId): JsonResponse
    {
        $screen = Screen::findOrFail($screenId);
        $settings = $screen->settings;

        if ($settings && $settings->logo_path) {
            if (!str_starts_with($settings->logo_path, 'http')) {
                Storage::disk('public')->delete($settings->logo_path);
            }
            $settings->update([
                'logo_path' => null,
                'logo_overlay_enabled' => false,
            ]);
        }

        return response()->json([
            'message' => 'Logo removed',
            'settings' => $settings?->fresh(),
        ]);
    }
}
