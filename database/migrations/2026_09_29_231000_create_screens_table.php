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
        Schema::create('screens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamp('last_ping_at')->nullable();
            $table->integer('default_slide_duration')->default(10); // in seconds
            $table->string('transition_effect')->default('fade'); // fade, slide, zoom, none
            $table->string('orientation')->default('landscape'); // landscape, portrait
            $table->string('resolution_hint')->nullable()->default('1920x1080');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('screens');
    }
};
