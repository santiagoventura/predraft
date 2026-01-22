<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerProjection extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'source',
        'season',
        // Hitter stats
        'pa', 'ab', 'h', 'doubles', 'triples', 'hr', 'r', 'rbi', 'sb', 'cs', 'hbp',
        'avg', 'obp', 'slg', 'ops',
        // Pitcher stats
        'ip', 'w', 'l', 'sv', 'hld', 'k', 'bb', 'h', 'er', 'qs', 'cg', 'shutouts',
        'era', 'whip', 'k_per_9', 'bb_per_9',
        'raw_data',
        'imported_at',
    ];

    protected $casts = [
        'season' => 'integer',
        'pa' => 'integer',
        'ab' => 'integer',
        'h' => 'integer',
        'doubles' => 'integer',
        'triples' => 'integer',
        'hr' => 'integer',
        'r' => 'integer',
        'rbi' => 'integer',
        'sb' => 'integer',
        'cs' => 'integer',
        'hbp' => 'integer',
        'avg' => 'decimal:3',
        'obp' => 'decimal:3',
        'slg' => 'decimal:3',
        'ops' => 'decimal:3',
        'ip' => 'decimal:1',
        'w' => 'integer',
        'l' => 'integer',
        'sv' => 'integer',
        'hld' => 'integer',
        'k' => 'integer',
        'bb' => 'integer',
        'er' => 'integer',
        'qs' => 'integer',
        'cg' => 'integer',
        'shutouts' => 'integer',
        'era' => 'decimal:2',
        'whip' => 'decimal:2',
        'k_per_9' => 'decimal:2',
        'bb_per_9' => 'decimal:2',
        'raw_data' => 'array',
        'imported_at' => 'datetime',
    ];

    /**
     * Get the player that owns this projection.
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}

