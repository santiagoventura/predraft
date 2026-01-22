<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DraftPick extends Model
{
    use HasFactory;

    protected $fillable = [
        'draft_id',
        'round',
        'pick_in_round',
        'overall_pick',
        'team_id',
        'player_id',
        'position_filled',
        'recommendations',
        'ai_explanation',
        'draft_context',
        'picked_at',
    ];

    protected $casts = [
        'round' => 'integer',
        'pick_in_round' => 'integer',
        'overall_pick' => 'integer',
        'recommendations' => 'array',
        'draft_context' => 'array',
        'picked_at' => 'datetime',
    ];

    /**
     * Get the draft this pick belongs to.
     */
    public function draft(): BelongsTo
    {
        return $this->belongsTo(Draft::class);
    }

    /**
     * Get the team that made this pick.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the player that was picked.
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Check if this pick has been made.
     */
    public function isPicked(): bool
    {
        return $this->player_id !== null;
    }

    /**
     * Get recommended players as Player models.
     */
    public function getRecommendedPlayers()
    {
        if (!$this->recommendations || !isset($this->recommendations['player_ids'])) {
            return collect();
        }

        return Player::whereIn('id', $this->recommendations['player_ids'])
            ->get()
            ->sortBy(function ($player) {
                return array_search($player->id, $this->recommendations['player_ids']);
            });
    }
}

