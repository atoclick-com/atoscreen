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
        Schema::create('slide_plays', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('screen_id')->constrained('screens')->cascadeOnDelete();
            $table->foreignId('slide_id')->constrained('slides')->cascadeOnDelete();
            $table->timestamp('played_at')->useCurrent();
            $table->integer('duration_seconds')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slide_plays');
    }
};
