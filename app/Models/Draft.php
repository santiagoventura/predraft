<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Draft extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'league_id',
        'name',
        'status',
        'draft_type',
        'current_round',
        'current_pick',
        'current_team_id',
        'total_rounds',
        'started_at',
        'completed_at',
        'settings',
    ];

    protected $casts = [
        'current_round' => 'integer',
        'current_pick' => 'integer',
        'total_rounds' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'settings' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // When a draft is deleted, also delete related picks and rosters
        static::deleting(function ($draft) {
            $draft->picks()->delete();
            $draft->rosters()->delete();
        });
    }

    /**
     * Get the league this draft belongs to.
     */
    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    /**
     * Get the current team on the clock.
     */
    public function currentTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    /**
     * Get all picks in this draft.
     */
    public function picks(): HasMany
    {
        return $this->hasMany(DraftPick::class)->orderBy('overall_pick');
    }

    /**
     * Get the rosters for this draft.
     */
    public function rosters(): HasMany
    {
        return $this->hasMany(TeamRoster::class);
    }

    /**
     * Check if the draft is complete.
     */
    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the draft is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }
}

