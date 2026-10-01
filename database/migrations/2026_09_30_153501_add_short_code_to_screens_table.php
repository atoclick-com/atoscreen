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
        Schema::table('screens', function (Blueprint $table) {
            $table->string('short_code')->nullable()->unique()->after('name');
        });

        // Backfill existing screens with clean 1-based sequential numbers
        $screens = Illuminate\Support\Facades\DB::table('screens')->orderBy('created_at', 'asc')->get();
        $code = 1;
        foreach ($screens as $screen) {
            Illuminate\Support\Facades\DB::table('screens')->where('id', $screen->id)->update([
                'short_code' => (string) $code++,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('screens', function (Blueprint $table) {
            $table->dropColumn('short_code');
        });
    }
};
