<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'league_id',
        'name',
        'draft_slot',
        'user_id',
        'is_user_team',
        'strategy_settings',
    ];

    protected $casts = [
        'draft_slot' => 'integer',
        'is_user_team' => 'boolean',
        'strategy_settings' => 'array',
    ];

    /**
     * Get the league this team belongs to.
     */
    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    /**
     * Get the user who owns this team.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the draft picks for this team.
     */
    public function draftPicks(): HasMany
    {
        return $this->hasMany(DraftPick::class);
    }

    /**
     * Get the roster for this team in a specific draft.
     */
    public function roster()
    {
        return $this->hasMany(TeamRoster::class);
    }
}

