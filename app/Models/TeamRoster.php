<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamRoster extends Model
{
    use HasFactory;

    protected $fillable = [
        'draft_id',
        'team_id',
        'player_id',
        'roster_position',
        'draft_pick_id',
    ];

    /**
     * Get the draft this roster belongs to.
     */
    public function draft(): BelongsTo
    {
        return $this->belongsTo(Draft::class);
    }

    /**
     * Get the team this roster belongs to.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the player on this roster.
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Get the draft pick that added this player.
     */
    public function draftPick(): BelongsTo
    {
        return $this->belongsTo(DraftPick::class);
    }
}

