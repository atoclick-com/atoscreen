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
        Schema::table('slides', function (Blueprint $table) {
            $table->boolean('audio_enabled')->nullable()->after('duration_override');
            $table->string('fit_mode')->default('ambient_blur')->after('audio_enabled'); // ambient_blur, contain, cover
        });

        Schema::table('screen_settings', function (Blueprint $table) {
            $table->boolean('audio_enabled')->default(false)->after('accent_color');
            $table->integer('audio_volume')->default(80)->after('audio_enabled'); // 0 to 100
            $table->string('aspect_ratio_mode')->default('ambient_blur')->after('audio_volume'); // ambient_blur, contain, cover
            $table->boolean('operating_hours_enabled')->default(false)->after('auto_refresh_interval');
            $table->string('opening_time')->nullable()->after('operating_hours_enabled'); // e.g. "09:00"
            $table->string('closing_time')->nullable()->after('opening_time'); // e.g. "23:00"
            $table->string('instagram_handle')->nullable()->after('closing_time'); // e.g. "@atofood_paris"
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('slides', function (Blueprint $table) {
            $table->dropColumn(['audio_enabled', 'fit_mode']);
        });

        Schema::table('screen_settings', function (Blueprint $table) {
            $table->dropColumn([
                'audio_enabled',
                'audio_volume',
                'aspect_ratio_mode',
                'operating_hours_enabled',
                'opening_time',
                'closing_time',
                'instagram_handle',
            ]);
        });
    }
};
