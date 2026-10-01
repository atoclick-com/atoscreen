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
        Schema::create('slides', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('screen_id')->constrained('screens')->cascadeOnDelete();
            $table->string('type')->default('image'); // image, video, html_promo
            $table->string('file_path')->nullable();
            $table->json('content')->nullable(); // For html_promo styling, copy, prices, colors
            $table->string('title');
            $table->integer('display_order')->default(0);
            $table->integer('duration_override')->nullable();
            $table->boolean('active')->default(true);
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->json('day_of_week_schedule')->nullable(); // [0,1,2,3,4,5,6] (0=Sun, 6=Sat)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slides');
    }
};
