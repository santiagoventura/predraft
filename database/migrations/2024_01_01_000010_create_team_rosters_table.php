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
        Schema::create('team_rosters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draft_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->string('roster_position'); // C, 1B, 2B, SS, 3B, OF, UTIL, P, etc.
            $table->foreignId('draft_pick_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();

            $table->unique(['draft_id', 'team_id', 'roster_position']);
            $table->index(['draft_id', 'team_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_rosters');
    }
};

