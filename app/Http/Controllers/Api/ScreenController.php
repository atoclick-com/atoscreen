<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Screen;
use App\Models\ScreenSetting;
use App\Models\Slide;
use App\Models\SlidePlay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ScreenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $screens = Screen::with(['settings', 'slides' => function ($query) {
            $query->orderBy('display_order', 'asc');
        }])
        ->orderBy('created_at', 'desc')
        ->get();

        $data = $screens->map(function ($screen) {
            $firstSlide = $screen->slides->first();
            $thumbnail = null;

            if ($firstSlide) {
                if ($firstSlide->type === 'image' || $firstSlide->type === 'video') {
                    $thumbnail = $firstSlide->file_url;
                } elseif ($firstSlide->type === 'html_promo' && !empty($firstSlide->content['image_url'])) {
                    $thumbnail = $firstSlide->content['image_url'];
                }
            }

            return [
                'id' => $screen->id,
                'name' => $screen->name,
                'status' => $screen->status,
                'is_online' => $screen->is_online,
                'last_ping_at' => $screen->last_ping_at,
                'last_ping_human' => $screen->last_ping_at ? $screen->last_ping_at->diffForHumans() : 'Never',
                'default_slide_duration' => $screen->default_slide_duration,
                'transition_effect' => $screen->transition_effect,
                'orientation' => $screen->orientation,
                'resolution_hint' => $screen->resolution_hint,
                'short_code' => $screen->short_code,
                'short_url' => $screen->short_url,
                'public_url' => $screen->public_url,
                'slides_count' => $screen->slides->count(),
                'active_slides_count' => $screen->slides->where('active', true)->count(),
                'thumbnail_preview' => $thumbnail,
                'thumbnail_type' => $firstSlide ? $firstSlide->type : null,
                'created_at' => $screen->created_at,
                'updated_at' => $screen->updated_at,
            ];
        });

        return response()->json([
            'screens' => $data,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'short_code' => 'nullable|string|max:30|unique:screens,short_code',
            'default_slide_duration' => 'nullable|integer|min:3|max:300',
            'transition_effect' => 'nullable|string|in:fade,slide,zoom,none',
            'orientation' => 'nullable|string|in:landscape,portrait',
            'resolution_hint' => 'nullable|string',
        ]);

        $appSettings = \App\Models\AppSetting::instance();

        $screen = Screen::create([
            'id' => (string) Str::uuid(),
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'short_code' => $validated['short_code'] ?? null,
            'default_slide_duration' => $validated['default_slide_duration'] ?? ($appSettings->default_slide_duration ?: 10),
            'transition_effect' => $validated['transition_effect'] ?? ($appSettings->default_transition ?: 'fade'),
            'orientation' => $validated['orientation'] ?? ($appSettings->default_orientation ?: 'landscape'),
            'resolution_hint' => $validated['resolution_hint'] ?? '1920x1080',
        ]);

        // Create default settings for this screen inherited from App Settings
        ScreenSetting::create([
            'screen_id' => $screen->id,
            'logo_overlay_enabled' => !empty($appSettings->logo_path),
            'logo_position' => 'top-right',
            'accent_color' => '#f59e0b',
            'ticker_enabled' => false,
            'ticker_text' => null,
            'ticker_speed' => 25,
            'clock_widget_enabled' => true,
            'clock_format' => '24h',
            'weather_widget_enabled' => false,
            'offline_fallback' => 'cached_playlist',
            'auto_refresh_interval' => 60,
            'operating_hours_enabled' => (bool)$appSettings->operating_hours_enabled,
            'opening_time' => $appSettings->opening_time ?: '08:00',
            'closing_time' => $appSettings->closing_time ?: '23:00',
            'instagram_handle' => $appSettings->instagram_handle,
            'screen_pin' => $appSettings->master_pin ?: '1234',
        ]);

        $screen->load(['settings', 'slides']);

        return response()->json([
            'message' => 'Screen created successfully',
            'screen' => $screen,
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $screen = Screen::with([
            'settings',
            'slides' => function ($query) {
                $query->orderBy('display_order', 'asc');
            }
        ])->findOrFail($id);

        return response()->json([
            'screen' => $screen,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $screen = Screen::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'short_code' => 'sometimes|nullable|string|max:30|unique:screens,short_code,' . $screen->id,
            'default_slide_duration' => 'nullable|integer|min:3|max:300',
            'transition_effect' => 'nullable|string|in:fade,slide,zoom,none',
            'orientation' => 'nullable|string|in:landscape,portrait',
            'resolution_hint' => 'nullable|string',
            'settings' => 'sometimes|array',
        ]);

        $screenFields = collect($validated)->except('settings')->all();
        if (array_key_exists('short_code', $screenFields)) {
            $code = trim((string)($screenFields['short_code'] ?? ''));
            $screenFields['short_code'] = $code !== '' ? $code : null;
        }
        if (!empty($screenFields)) {
            $screen->update($screenFields);
        }

        if ($request->has('settings') && is_array($request->input('settings'))) {
            $settingsData = $request->input('settings');
            $settings = $screen->settings ?: new ScreenSetting(['screen_id' => $screen->id]);
            $settings->fill($settingsData);
            $settings->save();

            // When aspect_ratio_mode is saved, propagate it to all slides on this screen
            if (!empty($settingsData['aspect_ratio_mode'])) {
                $screen->slides()->update(['fit_mode' => $settingsData['aspect_ratio_mode']]);
            }
            $screen->touch();
        }

        return response()->json([
            'message' => 'Screen updated successfully',
            'screen' => $screen->fresh(['settings', 'slides']),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $screen = Screen::findOrFail($id);
        $screen->delete();

        return response()->json([
            'message' => 'Screen deleted successfully',
        ]);
    }

    public function regenerateUrl(string $id): JsonResponse
    {
        $screen = Screen::findOrFail($id);
        $newUuid = (string) Str::uuid();
        $newShortCode = Screen::generateNextShortCode();

        // In SQLite, PRAGMA foreign_keys = OFF must be executed outside of active transactions
        DB::statement('PRAGMA foreign_keys = OFF;');
        try {
            DB::transaction(function () use ($screen, $newUuid, $newShortCode) {
                DB::table('screens')->where('id', $screen->id)->update([
                    'id' => $newUuid,
                    'short_code' => $newShortCode,
                    'updated_at' => now(),
                ]);
                DB::table('slides')->where('screen_id', $screen->id)->update(['screen_id' => $newUuid]);
                DB::table('screen_settings')->where('screen_id', $screen->id)->update(['screen_id' => $newUuid]);
                DB::table('slide_plays')->where('screen_id', $screen->id)->update(['screen_id' => $newUuid]);
            });
        } finally {
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        $updated = Screen::with(['settings', 'slides'])->findOrFail($newUuid);

        return response()->json([
            'message' => 'Screen display URL regenerated successfully. Old link has been invalidated.',
            'screen' => $updated,
            'public_url' => $updated->public_url,
            'short_url' => $updated->short_url,
        ]);
    }

    public function reset(string $id): JsonResponse
    {
        $screen = Screen::findOrFail($id);

        // Delete all slides for this screen
        $screen->slides()->delete();

        // Reset settings to defaults
        if ($screen->settings) {
            $screen->settings->update([
                'logo_overlay_enabled' => false,
                'logo_path' => null,
                'ticker_enabled' => false,
                'ticker_text' => null,
                'accent_color' => '#3b82f6',
            ]);
        }

        return response()->json([
            'message' => 'Screen reset to default state. All slides cleared.',
            'screen' => $screen->fresh(['settings', 'slides']),
        ]);
    }

    public function analytics(string $id): JsonResponse
    {
        $screen = Screen::with('slides')->findOrFail($id);

        // Aggregate plays per slide
        $playCounts = SlidePlay::where('screen_id', $screen->id)
            ->select('slide_id', DB::raw('count(*) as total_plays'))
            ->groupBy('slide_id')
            ->pluck('total_plays', 'slide_id');

        $slidesAnalytics = $screen->slides->map(function ($slide) use ($playCounts) {
            return [
                'slide_id' => $slide->id,
                'title' => $slide->title,
                'type' => $slide->type,
                'active' => $slide->active,
                'plays' => $playCounts[$slide->id] ?? 0,
            ];
        });

        $totalPlays = $slidesAnalytics->sum('plays');

        return response()->json([
            'screen_id' => $screen->id,
            'screen_name' => $screen->name,
            'total_plays' => $totalPlays,
            'is_online' => $screen->is_online,
            'last_ping_at' => $screen->last_ping_at,
            'slides' => $slidesAnalytics,
        ]);
    }
}
