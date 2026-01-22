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
        Schema::create('leagues', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('num_teams')->default(12);
            $table->enum('scoring_format', ['roto', 'h2h_categories', 'h2h_points'])->default('roto');
            $table->json('scoring_categories')->nullable(); // e.g., ["HR", "RBI", "SB", "AVG", "OPS"]
            $table->json('settings')->nullable(); // Additional league settings
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leagues');
    }
};

