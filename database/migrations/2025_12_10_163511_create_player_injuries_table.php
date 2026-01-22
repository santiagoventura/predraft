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
        Schema::create('player_injuries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->string('injury_type')->nullable(); // e.g., "Knee", "Elbow", "Back"
            $table->string('status'); // e.g., "Out for season", "60-day IL", "Day-to-day"
            $table->text('description')->nullable(); // Detailed description
            $table->date('injury_date')->nullable();
            $table->date('expected_return')->nullable();
            $table->integer('season')->default(2026);
            $table->string('source')->default('manual'); // 'manual', 'cbs', 'espn', etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['player_id', 'season', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_injuries');
    }
};
