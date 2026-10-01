<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Screen;
use App\Models\Slide;
use App\Models\ScreenSetting;
use App\Models\SlidePlay;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        SlidePlay::truncate();
        Slide::truncate();
        ScreenSetting::truncate();
        Screen::truncate();

        // 1. Create Default Admin User
        $user = User::updateOrCreate(
            ['email' => 'admin@atofood.com'],
            [
                'name' => 'Restaurant Manager',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        // 2. Create Primary Screen: "Front Window TV"
        $screen1 = Screen::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'name' => 'Front Window TV',
            'last_ping_at' => now(),
            'default_slide_duration' => 10,
            'transition_effect' => 'fade',
            'orientation' => 'landscape',
            'resolution_hint' => '1920x1080',
        ]);

        // Screen 1 Settings
        ScreenSetting::create([
            'screen_id' => $screen1->id,
            'logo_overlay_enabled' => true,
            'logo_path' => null, // Display will fall back to SVG brand badge if null
            'logo_position' => 'top-right',
            'accent_color' => '#f59e0b',
            'ticker_enabled' => true,
            'ticker_text' => '✨ Welcome to AtoFood Bistro! Today\'s Chef Special: Truffle Wagyu Burger + Craft Brew for $22 • Happy Hour Daily 5 PM - 7 PM • Free High-Speed Wi-Fi: AtoFood-Guest ✨',
            'ticker_speed' => 25,
            'clock_widget_enabled' => true,
            'clock_format' => '24h',
            'weather_widget_enabled' => true,
            'weather_city' => 'Paris',
            'offline_fallback' => 'cached_playlist',
            'auto_refresh_interval' => 60,
            'screen_pin' => '1234',
        ]);

        // Screen 1 Slides
        $slide1 = Slide::create([
            'screen_id' => $screen1->id,
            'type' => 'html_promo',
            'title' => 'Chef Signature Burger Special',
            'display_order' => 1,
            'duration_override' => 10,
            'active' => true,
            'content' => [
                'badge' => '🔥 CHEF SPECIAL OF THE DAY',
                'headline' => 'Double Truffle Wagyu Burger',
                'description' => '200g prime dry-aged Wagyu beef, melted aged cheddar, caramelized shallots, black winter truffle aioli on a toasted brioche bun.',
                'price' => '$19.50',
                'price_subtitle' => 'Includes hand-cut rustic fries',
                'bg_gradient' => 'linear-gradient(135deg, #18181b 0%, #09090b 100%)',
                'accent_color' => '#f59e0b',
                'image_url' => 'https://images.unsplash.com/photo-1550547660-d9450f859349?auto=format&fit=crop&w=1200&q=80',
            ],
        ]);

        $slide2 = Slide::create([
            'screen_id' => $screen1->id,
            'type' => 'image',
            'file_path' => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=1920&q=80',
            'title' => 'Woodfired Neapolitan Pizza',
            'display_order' => 2,
            'duration_override' => 10,
            'active' => true,
        ]);

        $slide3 = Slide::create([
            'screen_id' => $screen1->id,
            'type' => 'html_promo',
            'title' => 'Golden Sunset Happy Hour',
            'display_order' => 3,
            'duration_override' => 12,
            'active' => true,
            'content' => [
                'badge' => '🍸 DAILY HAPPY HOUR 17:00 - 19:00',
                'headline' => 'Craft Cocktails & Local Draughts',
                'description' => '50% off all signature mixology drinks, spritz varieties, and local IPA taps. Pair with our tapas sharing platter.',
                'price' => 'FROM $6.00',
                'price_subtitle' => 'Ask your server for our seasonal cocktail book',
                'bg_gradient' => 'linear-gradient(135deg, #1e1b4b 0%, #0f172a 100%)',
                'accent_color' => '#38bdf8',
                'image_url' => 'https://images.unsplash.com/photo-1514933651103-005eec06c04b?auto=format&fit=crop&w=1200&q=80',
            ],
        ]);

        $slide4 = Slide::create([
            'screen_id' => $screen1->id,
            'type' => 'image',
            'file_path' => 'https://images.unsplash.com/photo-1551024709-8f23befc6f87?auto=format&fit=crop&w=1920&q=80',
            'title' => 'Artisan Dessert Assortment',
            'display_order' => 4,
            'duration_override' => 8,
            'active' => true,
        ]);

        // 3. Create Secondary Screen: "Counter & Bar Screen"
        $screen2 = Screen::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'name' => 'Order Counter Screen',
            'last_ping_at' => now()->subMinutes(15), // Simulate offline
            'default_slide_duration' => 8,
            'transition_effect' => 'slide',
            'orientation' => 'landscape',
            'resolution_hint' => '1920x1080',
        ]);

        ScreenSetting::create([
            'screen_id' => $screen2->id,
            'logo_overlay_enabled' => true,
            'logo_position' => 'top-left',
            'accent_color' => '#10b981',
            'ticker_enabled' => true,
            'ticker_text' => 'Order with our QR Code at your table for faster service • Dessert combo available today',
            'ticker_speed' => 20,
            'clock_widget_enabled' => true,
            'clock_format' => '12h',
            'weather_widget_enabled' => false,
            'offline_fallback' => 'cached_playlist',
            'auto_refresh_interval' => 60,
        ]);

        Slide::create([
            'screen_id' => $screen2->id,
            'type' => 'html_promo',
            'title' => 'Barista Morning Combo',
            'display_order' => 1,
            'duration_override' => 8,
            'active' => true,
            'content' => [
                'badge' => '☕ MORNING EXPRESS',
                'headline' => 'Flat White & Butter Croissant',
                'description' => 'Freshly baked flaky pastry paired with our single-origin Ethiopian espresso.',
                'price' => '$5.90',
                'price_subtitle' => 'Available until 11:30 AM',
                'bg_gradient' => 'linear-gradient(135deg, #14532d 0%, #064e3b 100%)',
                'accent_color' => '#34d399',
                'image_url' => 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=1200&q=80',
            ],
        ]);

        // 4. Seed some sample analytics plays for screen1
        for ($i = 0; $i < 35; $i++) {
            SlidePlay::create([
                'screen_id' => $screen1->id,
                'slide_id' => $slide1->id,
                'played_at' => now()->subHours(rand(1, 24)),
                'duration_seconds' => 10,
            ]);
        }
        for ($i = 0; $i < 28; $i++) {
            SlidePlay::create([
                'screen_id' => $screen1->id,
                'slide_id' => $slide2->id,
                'played_at' => now()->subHours(rand(1, 24)),
                'duration_seconds' => 10,
            ]);
        }
        for ($i = 0; $i < 42; $i++) {
            SlidePlay::create([
                'screen_id' => $screen1->id,
                'slide_id' => $slide3->id,
                'played_at' => now()->subHours(rand(1, 24)),
                'duration_seconds' => 12,
            ]);
        }
    }
}
