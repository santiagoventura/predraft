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
        Schema::create('player_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->foreignId('league_id')->constrained()->onDelete('cascade');
            $table->integer('season')->default(2025);
            $table->string('projection_source')->default('fantasypros'); // Which projection to use
            $table->decimal('total_points', 10, 2)->default(0); // Calculated total points
            $table->json('category_breakdown')->nullable(); // Breakdown by category
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->index(['league_id', 'season', 'total_points']);
            $table->unique(['player_id', 'league_id', 'season', 'projection_source'], 'player_scores_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_scores');
    }
};

