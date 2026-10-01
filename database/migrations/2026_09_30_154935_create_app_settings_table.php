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
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('app_name')->default('AtoFood Signage');
            $table->string('business_name')->default('Trotiluxe');
            $table->string('display_domain')->default('trotiluxe.ma');
            $table->integer('default_slide_duration')->default(10);
            $table->string('default_transition')->default('fade');
            $table->string('default_orientation')->default('landscape');
            $table->boolean('operating_hours_enabled')->default(false);
            $table->string('opening_time')->nullable()->default('08:00');
            $table->string('closing_time')->nullable()->default('23:00');
            $table->string('instagram_handle')->nullable()->default('@trotiluxe');
            $table->string('contact_email')->nullable()->default('contact@trotiluxe.ma');
            $table->string('contact_phone')->nullable();
            $table->string('master_pin')->nullable()->default('1234');
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });

        Illuminate\Support\Facades\DB::table('app_settings')->insert([
            'app_name' => 'AtoFood Signage',
            'business_name' => 'Trotiluxe',
            'display_domain' => 'trotiluxe.ma',
            'default_slide_duration' => 10,
            'default_transition' => 'fade',
            'default_orientation' => 'landscape',
            'operating_hours_enabled' => false,
            'opening_time' => '08:00',
            'closing_time' => '23:00',
            'instagram_handle' => '@trotiluxe',
            'contact_email' => 'contact@trotiluxe.ma',
            'master_pin' => '1234',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
