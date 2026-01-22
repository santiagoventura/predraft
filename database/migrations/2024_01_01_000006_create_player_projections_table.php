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
        Schema::create('player_projections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->string('source'); // e.g., 'fantasypros', 'steamer', 'zips'
            $table->integer('season')->default(2025);
            
            // Hitter stats
            $table->integer('pa')->nullable(); // Plate Appearances
            $table->integer('ab')->nullable(); // At Bats
            $table->integer('h')->nullable(); // Hits
            $table->integer('hr')->nullable(); // Home Runs
            $table->integer('r')->nullable(); // Runs
            $table->integer('rbi')->nullable();
            $table->integer('sb')->nullable(); // Stolen Bases
            $table->decimal('avg', 4, 3)->nullable(); // Batting Average
            $table->decimal('obp', 4, 3)->nullable(); // On-Base Percentage
            $table->decimal('slg', 4, 3)->nullable(); // Slugging Percentage
            $table->decimal('ops', 4, 3)->nullable();
            
            // Pitcher stats
            $table->decimal('ip', 5, 1)->nullable(); // Innings Pitched
            $table->integer('w')->nullable(); // Wins
            $table->integer('l')->nullable(); // Losses
            $table->integer('sv')->nullable(); // Saves
            $table->integer('hld')->nullable(); // Holds
            $table->integer('k')->nullable(); // Strikeouts
            $table->integer('bb')->nullable(); // Walks
            $table->decimal('era', 4, 2)->nullable();
            $table->decimal('whip', 4, 2)->nullable();
            $table->decimal('k_per_9', 4, 2)->nullable();
            $table->decimal('bb_per_9', 4, 2)->nullable();
            
            $table->json('raw_data')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->index(['player_id', 'source', 'season']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_projections');
    }
};

