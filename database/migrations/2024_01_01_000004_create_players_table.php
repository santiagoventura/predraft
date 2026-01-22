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
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('mlb_team')->nullable();
            $table->string('positions'); // Comma-separated or JSON: "1B,3B" or "OF"
            $table->string('primary_position')->nullable();
            $table->boolean('is_pitcher')->default(false);
            $table->enum('bats', ['R', 'L', 'S'])->nullable(); // Right, Left, Switch
            $table->enum('throws', ['R', 'L'])->nullable();
            $table->integer('age')->nullable();
            $table->string('external_id')->nullable(); // For FantasyPros or other sources
            $table->json('metadata')->nullable(); // Additional player info
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('mlb_team');
            $table->index('is_pitcher');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};

