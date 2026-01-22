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
        Schema::create('league_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->onDelete('cascade');
            $table->string('position_code'); // C, 1B, 2B, SS, 3B, OF, UTIL, P, SP, RP
            $table->integer('slot_count')->default(1);
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->unique(['league_id', 'position_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('league_positions');
    }
};

