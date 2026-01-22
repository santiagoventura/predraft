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
        Schema::create('draft_picks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draft_id')->constrained()->onDelete('cascade');
            $table->integer('round');
            $table->integer('pick_in_round');
            $table->integer('overall_pick');
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('player_id')->nullable()->constrained()->onDelete('set null');
            $table->string('position_filled')->nullable(); // Which roster slot this filled
            $table->json('recommendations')->nullable(); // AI's top 5 player IDs
            $table->text('ai_explanation')->nullable(); // Full AI analysis
            $table->json('draft_context')->nullable(); // Snapshot of draft state at this pick
            $table->timestamp('picked_at')->nullable();
            $table->timestamps();

            $table->index(['draft_id', 'overall_pick']);
            $table->index(['draft_id', 'team_id']);
            $table->unique(['draft_id', 'overall_pick']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draft_picks');
    }
};

