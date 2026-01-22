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
        Schema::table('player_projections', function (Blueprint $table) {
            // Additional batter stats
            $table->integer('doubles')->nullable()->after('h'); // 2B
            $table->integer('triples')->nullable()->after('doubles'); // 3B
            $table->integer('cs')->nullable()->after('sb'); // Caught Stealing
            $table->integer('hbp')->nullable()->after('bb'); // Hit By Pitch

            // Additional pitcher stats
            $table->integer('er')->nullable()->after('bb'); // Earned Runs
            $table->integer('cg')->nullable()->after('k_per_9'); // Complete Games
            $table->integer('shutouts')->nullable()->after('cg'); // Shutouts
            $table->integer('no_hitters')->nullable()->after('shutouts'); // No Hitters
            $table->integer('perfect_games')->nullable()->after('no_hitters'); // Perfect Games
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('player_projections', function (Blueprint $table) {
            $table->dropColumn(['doubles', 'triples', 'cs', 'hbp', 'er', 'cg', 'shutouts', 'no_hitters', 'perfect_games']);
        });
    }
};

