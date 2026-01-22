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
        Schema::create('league_scoring_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->onDelete('cascade');
            $table->enum('player_type', ['batter', 'pitcher']); // Which type of player this applies to
            $table->string('stat_code'); // e.g., 'HR', 'RBI', 'K', 'W', 'SV'
            $table->string('stat_name'); // e.g., 'Home Runs', 'Strikeouts'
            $table->decimal('points_per_unit', 10, 4); // Points awarded per unit of this stat
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['league_id', 'player_type']);
            $table->unique(['league_id', 'player_type', 'stat_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('league_scoring_categories');
    }
};

