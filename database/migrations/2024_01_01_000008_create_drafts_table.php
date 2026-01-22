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
        Schema::create('drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('status', ['setup', 'in_progress', 'paused', 'completed'])->default('setup');
            $table->enum('draft_type', ['snake', 'linear', 'auction'])->default('snake');
            $table->integer('current_round')->default(1);
            $table->integer('current_pick')->default(1);
            $table->foreignId('current_team_id')->nullable()->constrained('teams')->onDelete('set null');
            $table->integer('total_rounds')->nullable(); // Calculated from roster positions
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('settings')->nullable(); // Draft-specific settings
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drafts');
    }
};

