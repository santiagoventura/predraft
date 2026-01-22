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
        Schema::create('player_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->string('source'); // e.g., 'fantasypros_hitters', 'fantasypros_pitchers', 'custom'
            $table->integer('season')->default(2025);
            $table->integer('overall_rank')->nullable();
            $table->integer('position_rank')->nullable();
            $table->decimal('adp', 5, 1)->nullable(); // Average Draft Position
            $table->integer('tier')->nullable();
            $table->json('raw_data')->nullable(); // Store original data from source
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->index(['player_id', 'source', 'season']);
            $table->index('overall_rank');
            $table->index('adp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_rankings');
    }
};

