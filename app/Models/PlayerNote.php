<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'tag',
        'note',
        'source',
        'user_id',
    ];

    /**
     * Get the player that owns this note.
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Get the user who created this note.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

