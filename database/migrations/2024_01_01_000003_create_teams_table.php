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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->integer('draft_slot'); // Position in draft order (1-based)
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->boolean('is_user_team')->default(false);
            $table->json('strategy_settings')->nullable(); // AI strategy preferences
            $table->timestamps();

            $table->unique(['league_id', 'draft_slot']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};

