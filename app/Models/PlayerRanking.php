<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerRanking extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'source',
        'season',
        'overall_rank',
        'position_rank',
        'adp',
        'tier',
        'raw_data',
        'imported_at',
    ];

    protected $casts = [
        'season' => 'integer',
        'overall_rank' => 'integer',
        'position_rank' => 'integer',
        'adp' => 'decimal:1',
        'tier' => 'integer',
        'raw_data' => 'array',
        'imported_at' => 'datetime',
    ];

    /**
     * Get the player that owns this ranking.
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}

