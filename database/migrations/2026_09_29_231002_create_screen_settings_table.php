<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('screen_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('screen_id')->unique()->constrained('screens')->cascadeOnDelete();
            $table->boolean('logo_overlay_enabled')->default(false);
            $table->string('logo_path')->nullable();
            $table->string('logo_position')->default('top-right'); // 3x3: top-left, top-center, top-right, center-left, center, center-right, bottom-left, bottom-center, bottom-right
            $table->string('accent_color')->default('#3b82f6');
            $table->boolean('ticker_enabled')->default(false);
            $table->text('ticker_text')->nullable();
            $table->integer('ticker_speed')->default(25); // seconds per loop
            $table->boolean('clock_widget_enabled')->default(true);
            $table->string('clock_format')->default('24h'); // 12h, 24h
            $table->boolean('weather_widget_enabled')->default(false);
            $table->string('weather_city')->nullable();
            $table->string('offline_fallback')->default('cached_playlist'); // cached_playlist, default_closed
            $table->integer('auto_refresh_interval')->default(60); // seconds
            $table->string('screen_pin')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('screen_settings');
    }
};
