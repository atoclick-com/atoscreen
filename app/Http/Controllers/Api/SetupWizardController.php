<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Screen;
use App\Models\ScreenSetting;
use App\Models\Slide;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SetupWizardController extends Controller
{
    /**
     * Provide pre-filled configuration and available screens for the wizard.
     */
    public function initialData(Request $request): JsonResponse
    {
        $appSettings = AppSetting::instance();
        $screens = Screen::with(['settings', 'slides'])->orderBy('created_at', 'asc')->get();
        $firstScreen = $screens->first();

        $host = $request->getHost();
        $port = $request->getPort();
        $isStandardPort = ($port == 80 || $port == 443 || empty($port));
        $currentDomain = $isStandardPort ? $host : "{$host}:{$port}";

        return response()->json([
            'settings' => $appSettings,
            'has_existing_screens' => $screens->count() > 0,
            'screens_count' => $screens->count(),
            'first_screen' => $firstScreen ? [
                'id' => $firstScreen->id,
                'name' => $firstScreen->name,
                'short_code' => $firstScreen->short_code,
                'short_url' => $firstScreen->short_url,
                'public_url' => $firstScreen->public_url,
                'orientation' => $firstScreen->orientation,
                'default_slide_duration' => $firstScreen->default_slide_duration,
                'transition_effect' => $firstScreen->transition_effect,
                'settings' => $firstScreen->settings,
                'slides_count' => $firstScreen->slides->count(),
            ] : null,
            'suggested_domain' => $appSettings->display_domain ?: $currentDomain,
        ]);
    }

    /**
     * Complete the full setup wizard in one atomic action.
     */
    public function complete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            // Brand & System
            'app_name' => 'required|string|max:100',
            'business_name' => 'nullable|string|max:150',
            'display_domain' => 'nullable|string|max:150',
            'master_pin' => 'nullable|string|max:20',
            'instagram_handle' => 'nullable|string|max:100',

            // Master Engine Defaults
            'default_slide_duration' => 'nullable|integer|min:3|max:300',
            'default_transition' => 'nullable|string|in:fade,slide,zoom,none',
            'default_orientation' => 'nullable|string|in:landscape,portrait',
            'operating_hours_enabled' => 'nullable|boolean',
            'opening_time' => 'nullable|string|max:10',
            'closing_time' => 'nullable|string|max:10',

            // Screen Setup
            'screen_id' => 'nullable|string',
            'screen_name' => 'required|string|max:100',
            'screen_short_code' => 'nullable|string|max:30',
            'screen_orientation' => 'nullable|string|in:landscape,portrait',
            'screen_resolution' => 'nullable|string|max:50',
            'clock_widget_enabled' => 'nullable|boolean',
            'clock_format' => 'nullable|string|in:12h,24h',
            'ticker_enabled' => 'nullable|boolean',
            'ticker_text' => 'nullable|string|max:500',

            // Starter Content
            'create_welcome_slide' => 'nullable|boolean',
            'welcome_headline' => 'nullable|string|max:150',
            'welcome_description' => 'nullable|string|max:500',
            'welcome_badge' => 'nullable|string|max:50',
            'create_promo_slide' => 'nullable|boolean',
            'promo_headline' => 'nullable|string|max:150',
            'promo_description' => 'nullable|string|max:500',
            'promo_price' => 'nullable|string|max:50',
            'promo_price_subtitle' => 'nullable|string|max:100',
            'promo_badge' => 'nullable|string|max:50',
        ]);

        $user = $request->user();

        return DB::transaction(function () use ($validated, $user) {
            // 1. Update Global App Settings
            $appSettings = AppSetting::instance();
            $appSettings->update([
                'app_name' => $validated['app_name'],
                'business_name' => $validated['business_name'] ?? $appSettings->business_name,
                'display_domain' => !empty($validated['display_domain'])
                    ? preg_replace('/^https?:\/\//i', '', rtrim($validated['display_domain'], '/'))
                    : $appSettings->display_domain,
                'master_pin' => $validated['master_pin'] ?? '1234',
                'instagram_handle' => $validated['instagram_handle'] ?? $appSettings->instagram_handle,
                'default_slide_duration' => $validated['default_slide_duration'] ?? 10,
                'default_transition' => $validated['default_transition'] ?? 'fade',
                'default_orientation' => $validated['default_orientation'] ?? 'landscape',
                'operating_hours_enabled' => (bool)($validated['operating_hours_enabled'] ?? false),
                'opening_time' => $validated['opening_time'] ?? '08:00',
                'closing_time' => $validated['closing_time'] ?? '23:00',
            ]);

            // 2. Find or Create Primary Screen
            $screen = null;
            if (!empty($validated['screen_id'])) {
                $screen = Screen::find($validated['screen_id']);
            }

            if (!$screen) {
                // Check if any screen exists to update, or create a brand new one
                $screen = Screen::first();
            }

            $orientation = $validated['screen_orientation'] ?? $validated['default_orientation'] ?? 'landscape';
            $duration = $validated['default_slide_duration'] ?? 10;
            $transition = $validated['default_transition'] ?? 'fade';
            $resolution = $validated['screen_resolution'] ?? ($orientation === 'portrait' ? '1080x1920' : '1920x1080');

            if ($screen) {
                // If short code is specified and different, ensure uniqueness
                $shortCode = $validated['screen_short_code'] ?? $screen->short_code;
                if (!empty($validated['screen_short_code']) && $validated['screen_short_code'] !== $screen->short_code) {
                    $exists = Screen::where('short_code', $validated['screen_short_code'])
                        ->where('id', '!=', $screen->id)
                        ->exists();
                    if ($exists) {
                        $shortCode = $screen->short_code;
                    }
                }

                $screen->update([
                    'name' => $validated['screen_name'],
                    'short_code' => $shortCode,
                    'orientation' => $orientation,
                    'resolution_hint' => $resolution,
                    'default_slide_duration' => $duration,
                    'transition_effect' => $transition,
                ]);
            } else {
                // Create brand new screen
                $shortCode = $validated['screen_short_code'] ?? '1';
                $exists = Screen::where('short_code', $shortCode)->exists();
                if ($exists) {
                    $shortCode = null; // Let model generate auto incremental code
                }

                $screen = Screen::create([
                    'id' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'name' => $validated['screen_name'],
                    'short_code' => $shortCode,
                    'orientation' => $orientation,
                    'resolution_hint' => $resolution,
                    'default_slide_duration' => $duration,
                    'transition_effect' => $transition,
                ]);
            }

            // 3. Update or Create Screen Settings
            $screenSetting = ScreenSetting::firstOrNew(['screen_id' => $screen->id]);
            $screenSetting->fill([
                'clock_widget_enabled' => (bool)($validated['clock_widget_enabled'] ?? true),
                'clock_format' => $validated['clock_format'] ?? '24h',
                'ticker_enabled' => (bool)($validated['ticker_enabled'] ?? false),
                'ticker_text' => $validated['ticker_text'] ?? null,
                'ticker_speed' => 25,
                'operating_hours_enabled' => (bool)($validated['operating_hours_enabled'] ?? false),
                'opening_time' => $validated['opening_time'] ?? '08:00',
                'closing_time' => $validated['closing_time'] ?? '23:00',
                'instagram_handle' => $validated['instagram_handle'] ?? $appSettings->instagram_handle,
                'screen_pin' => $validated['master_pin'] ?? '1234',
                'accent_color' => '#f59e0b',
                'aspect_ratio_mode' => 'cover',
            ]);
            $screenSetting->save();

            // 4. Starter Content Generation (if requested)
            $createdSlidesCount = 0;
            $currentOrder = $screen->slides()->max('display_order') ?? 0;

            // Welcome Slide
            if (!empty($validated['create_welcome_slide'])) {
                $welcomeHeadline = $validated['welcome_headline'] ?: ("Welcome to " . ($validated['business_name'] ?: 'Our Venue'));
                $welcomeDesc = $validated['welcome_description'] ?: "Enjoy our handcrafted delights, seasonal specials, and premium ambiance.";
                $welcomeBadge = $validated['welcome_badge'] ?: "Welcome";

                Slide::create([
                    'screen_id' => $screen->id,
                    'type' => 'html_promo',
                    'title' => 'Welcome Display Slide',
                    'display_order' => ++$currentOrder,
                    'active' => true,
                    'fit_mode' => 'cover',
                    'duration_override' => null,
                    'content' => [
                        'headline' => $welcomeHeadline,
                        'description' => $welcomeDesc,
                        'badge' => $welcomeBadge,
                        'accent_color' => '#f59e0b',
                        'bg_gradient' => 'linear-gradient(135deg, #1c1917 0%, #0c0a09 100%)',
                    ],
                ]);
                $createdSlidesCount++;
            }

            // Promo Slide
            if (!empty($validated['create_promo_slide'])) {
                $promoHeadline = $validated['promo_headline'] ?: "Special Daily Promotion";
                $promoDesc = $validated['promo_description'] ?: "Ask our staff about today's highlighted chef specials and refreshment offers.";
                $promoBadge = $validated['promo_badge'] ?: "Limited Offer";
                $promoPrice = $validated['promo_price'] ?: "20% OFF";
                $promoSubtitle = $validated['promo_price_subtitle'] ?: "Selected favorites";

                Slide::create([
                    'screen_id' => $screen->id,
                    'type' => 'html_promo',
                    'title' => 'Featured Promotion Card',
                    'display_order' => ++$currentOrder,
                    'active' => true,
                    'fit_mode' => 'cover',
                    'duration_override' => null,
                    'content' => [
                        'headline' => $promoHeadline,
                        'description' => $promoDesc,
                        'badge' => $promoBadge,
                        'price' => $promoPrice,
                        'price_subtitle' => $promoSubtitle,
                        'accent_color' => '#fbbf24',
                        'bg_gradient' => 'linear-gradient(135deg, #27272a 0%, #09090b 100%)',
                    ],
                ]);
                $createdSlidesCount++;
            }

            $screen->load(['settings', 'slides']);

            // Mark system as installed so CheckInstallation middleware unlocks the full app
            @touch(storage_path('installed'));

            return response()->json([
                'success' => true,
                'message' => 'Setup wizard completed successfully!',
                'screen' => [
                    'id' => $screen->id,
                    'name' => $screen->name,
                    'short_code' => $screen->short_code,
                    'short_url' => $screen->short_url,
                    'public_url' => $screen->public_url,
                    'orientation' => $screen->orientation,
                    'slides_count' => $screen->slides->count(),
                ],
                'settings' => $appSettings,
                'created_slides_count' => $createdSlidesCount,
                'tv_urls' => [
                    'short_url' => $screen->short_url ? "https://{$screen->short_url}" : null,
                    'local_display_url' => url('/v/' . ($screen->short_code ?: $screen->id)),
                    'dashboard_url' => url('/'),
                    'editor_url' => url("/screens/{$screen->id}"),
                ],
            ]);
        });
    }
}
