<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayerInjury extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'injury_type',
        'status',
        'description',
        'injury_date',
        'expected_return',
        'season',
        'source',
        'is_active',
    ];

    protected $casts = [
        'injury_date' => 'date',
        'expected_return' => 'date',
        'is_active' => 'boolean',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Get formatted injury status for display
     */
    public function getFormattedStatus(): string
    {
        $parts = [];
        
        if ($this->status) {
            $parts[] = $this->status;
        }
        
        if ($this->injury_type) {
            $parts[] = $this->injury_type;
        }
        
        if ($this->description) {
            $parts[] = $this->description;
        }
        
        return implode(' - ', $parts) ?: 'Injury reported';
    }
}

