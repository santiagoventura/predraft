<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'league_id',
        'season',
        'projection_source',
        'total_points',
        'category_breakdown',
        'calculated_at',
    ];

    protected $casts = [
        'total_points' => 'decimal:2',
        'category_breakdown' => 'array',
        'calculated_at' => 'datetime',
    ];

    /**
     * Get the player that owns this score.
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Get the league that owns this score.
     */
    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }
}

