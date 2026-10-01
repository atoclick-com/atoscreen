<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Screen;
use App\Models\Slide;
use App\Models\SlidePlay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DisplayController extends Controller
{
    /**
     * Public endpoint to fetch the active playlist and screen config.
     */
    public function playlist(string $uuid): JsonResponse
    {
        $screen = Screen::with(['settings', 'slides' => function ($query) {
            $query->where('active', true)->orderBy('display_order', 'asc');
        }])
        ->where('id', $uuid)
        ->orWhere('short_code', (string) $uuid)
        ->first();

        if (!$screen) {
            return response()->json([
                'error' => 'Screen not found or display URL is invalid.',
            ], 404);
        }

        // Filter slides based on date and day-of-week schedule
        $activeSlides = $screen->slides->filter(function ($slide) {
            return $slide->is_in_schedule;
        })->values();

        $appSettings = \App\Models\AppSetting::instance();

        // Screen specific settings with fallback to global AppSettings
        $screenSettings = $screen->settings;
        $effectiveLogoUrl = $screenSettings?->logo_url ?: $appSettings->logo_url;
        $effectiveOperatingHours = (bool) ($screenSettings?->operating_hours_enabled ?: $appSettings->operating_hours_enabled);
        $effectiveOpeningTime = ($screenSettings?->operating_hours_enabled && $screenSettings?->opening_time)
            ? $screenSettings->opening_time
            : ($appSettings->opening_time ?: '08:00');
        $effectiveClosingTime = ($screenSettings?->operating_hours_enabled && $screenSettings?->closing_time)
            ? $screenSettings->closing_time
            : ($appSettings->closing_time ?: '23:00');

        $settings = (object)[
            'logo_overlay_enabled' => $screenSettings ? (bool)$screenSettings->logo_overlay_enabled : !empty($effectiveLogoUrl),
            'logo_url' => $effectiveLogoUrl,
            'logo_position' => $screenSettings?->logo_position ?: 'top-right',
            'accent_color' => $screenSettings?->accent_color ?: '#f59e0b',
            'audio_enabled' => (bool) ($screenSettings?->audio_enabled ?? false),
            'audio_volume' => (int) ($screenSettings?->audio_volume ?? 80),
            'aspect_ratio_mode' => $screenSettings?->aspect_ratio_mode ?: 'ambient_blur',
            'ticker_enabled' => (bool) ($screenSettings?->ticker_enabled ?? false),
            'ticker_text' => $screenSettings?->ticker_text,
            'ticker_speed' => (int) ($screenSettings?->ticker_speed ?? 25),
            'clock_widget_enabled' => $screenSettings ? (bool)$screenSettings->clock_widget_enabled : true,
            'clock_format' => $screenSettings?->clock_format ?: '24h',
            'weather_widget_enabled' => (bool) ($screenSettings?->weather_widget_enabled ?? false),
            'weather_city' => $screenSettings?->weather_city,
            'offline_fallback' => $screenSettings?->offline_fallback ?: 'cached_playlist',
            'auto_refresh_interval' => (int) ($screenSettings?->auto_refresh_interval ?? 60),
            'operating_hours_enabled' => $effectiveOperatingHours,
            'opening_time' => $effectiveOpeningTime,
            'closing_time' => $effectiveClosingTime,
            'instagram_handle' => $screenSettings?->instagram_handle ?: $appSettings->instagram_handle,
            'screen_pin' => $screenSettings?->screen_pin ?: ($appSettings->master_pin ?: '1234'),
            'business_name' => $appSettings->business_name ?: 'AtoFood',
            'app_name' => $appSettings->app_name ?: 'AtoFood Signage',
        ];

        $defaultDuration = $screen->default_slide_duration ?: ($appSettings->default_slide_duration ?: 10);

        $screenFitMode = $settings->aspect_ratio_mode ?: 'cover';

        $formattedSlides = $activeSlides->map(function ($slide) use ($screen, $settings, $defaultDuration, $screenFitMode) {
            $isMediaWithAudio = in_array($slide->type, ['video', 'instagram']);
            $audioEnabled = !is_null($slide->audio_enabled)
                ? (bool) $slide->audio_enabled
                : (bool) ($settings->audio_enabled ?? false);

            if ($settings->audio_enabled && $isMediaWithAudio && $slide->audio_enabled !== false) {
                $audioEnabled = true;
            }

            $effectiveFitMode = $slide->fit_mode;
            if (!$effectiveFitMode || ($effectiveFitMode === 'ambient_blur' && in_array($screenFitMode, ['cover', 'contain']))) {
                $effectiveFitMode = $screenFitMode;
            }

            $filePath = $slide->file_path;
            $content = is_array($slide->content) ? $slide->content : [];

            // Auto-detect and attach local reel video cache if missing on the slide record
            if ($slide->type === 'instagram' && empty($filePath)) {
                $url = $content['url'] ?? '';
                preg_match('/instagram\.com\/(?:p|reel|tv)\/([a-zA-Z0-9_-]+)/i', $url, $matches);
                $shortcode = $matches[1] ?? '';

                $candidates = [
                    "screens/{$screen->id}/instagram/{$shortcode}.mp4",
                    "screens/instagram/{$shortcode}.mp4",
                    "screens/instagram/trotiluxe.mp4",
                ];

                foreach ($candidates as $cand) {
                    $fullPath = storage_path("app/public/{$cand}");
                    if (file_exists($fullPath) && filesize($fullPath) > 50000) {
                        $filePath = $cand;
                        $content['media_url'] = url("storage/{$cand}");
                        $content['is_video'] = true;
                        $content['media_type'] = 'reel';
                        break;
                    }
                }
            }

            $fileUrl = null;
            if ($filePath) {
                if (str_starts_with($filePath, 'http://') || str_starts_with($filePath, 'https://')) {
                    $fileUrl = $filePath;
                } else {
                    $clean = ltrim($filePath, '/');
                    if (str_starts_with($clean, 'storage/')) {
                        $clean = substr($clean, 8);
                    }
                    $fileUrl = url('storage/' . $clean);
                }
            }

            return [
                'id' => $slide->id,
                'type' => $slide->type, // image, video, html_promo, instagram
                'title' => $slide->title,
                'file_url' => $fileUrl,
                'content' => $content,
                'duration' => $slide->duration_override ?: $defaultDuration,
                'display_order' => $slide->display_order,
                'audio_enabled' => $audioEnabled,
                'fit_mode' => $effectiveFitMode,
            ];
        });

        // Generate checksum hash so display client detects any slide or setting modification
        $playlistChecksum = md5(json_encode([
            'screen_updated' => $screen->updated_at?->timestamp,
            'settings' => (array) $settings,
            'slides' => $formattedSlides->toArray(),
            'app_updated' => $appSettings->updated_at?->timestamp,
        ]));

        return response()->json([
            'screen' => [
                'id' => $screen->id,
                'name' => $screen->name,
                'short_code' => $screen->short_code,
                'short_url' => $screen->short_url,
                'public_url' => $screen->public_url,
                'default_slide_duration' => $defaultDuration,
                'transition_effect' => $screen->transition_effect ?: ($appSettings->default_transition ?: 'fade'),
                'orientation' => $screen->orientation ?: ($appSettings->default_orientation ?: 'landscape'),
                'resolution_hint' => $screen->resolution_hint,
                'updated_at' => $screen->updated_at,
            ],
            'settings' => $settings,
            'slides' => $formattedSlides,
            'total_slides' => $formattedSlides->count(),
            'checksum' => $playlistChecksum,
            'polled_at' => now()->toISOString(),
        ]);
    }

    /**
     * Heartbeat ping sent periodically by the display client.
     */
    public function ping(Request $request, string $uuid): JsonResponse
    {
        $screen = Screen::where('id', $uuid)
            ->orWhere('short_code', (string) $uuid)
            ->first();

        if (!$screen) {
            return response()->json(['error' => 'Screen not found'], 404);
        }

        $screen->forceFill([
            'last_ping_at' => now(),
        ])->saveQuietly();

        // If client reported which slide is playing, log to slide_plays
        if ($request->filled('current_slide_id')) {
            $slide = Slide::where('id', $request->input('current_slide_id'))
                ->where('screen_id', $screen->id)
                ->first();

            if ($slide) {
                SlidePlay::create([
                    'screen_id' => $screen->id,
                    'slide_id' => $slide->id,
                    'played_at' => now(),
                    'duration_seconds' => $request->input('duration_seconds', null),
                ]);
            }
        }

        $appSettings = \App\Models\AppSetting::instance();
        $latestUpdated = $screen->updated_at;
        if ($appSettings->updated_at && $appSettings->updated_at->gt($latestUpdated)) {
            $latestUpdated = $appSettings->updated_at;
        }

        return response()->json([
            'status' => 'ok',
            'server_time' => now()->toISOString(),
            'screen_updated_at' => $latestUpdated->toISOString(),
            'auto_refresh_interval' => $screen->settings?->auto_refresh_interval ?? 60,
        ]);
    }
}
