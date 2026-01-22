<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaguePosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'league_id',
        'position_code',
        'slot_count',
        'display_order',
    ];

    protected $casts = [
        'slot_count' => 'integer',
        'display_order' => 'integer',
    ];

    /**
     * Get the league that owns this position.
     */
    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }
}

