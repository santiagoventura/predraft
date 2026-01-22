<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Player extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'mlb_team',
        'positions',
        'primary_position',
        'is_pitcher',
        'bats',
        'throws',
        'age',
        'external_id',
        'metadata',
    ];

    protected $casts = [
        'is_pitcher' => 'boolean',
        'age' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the rankings for this player.
     */
    public function rankings(): HasMany
    {
        return $this->hasMany(PlayerRanking::class);
    }

    /**
     * Get the projections for this player.
     */
    public function projections(): HasMany
    {
        return $this->hasMany(PlayerProjection::class);
    }

    /**
     * Get the latest projection as a relationship (for eager loading).
     */
    public function latestProjection(): HasOne
    {
        return $this->hasOne(PlayerProjection::class)
            ->where('source', 'fantasypros')
            ->where('season', 2026)
            ->latestOfMany('imported_at');
    }

    /**
     * Get the notes for this player.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(PlayerNote::class);
    }

    /**
     * Get the injuries for this player.
     */
    public function injuries(): HasMany
    {
        return $this->hasMany(PlayerInjury::class);
    }

    /**
     * Get active injury for current season
     */
    public function getCurrentInjury(int $season = 2026)
    {
        return $this->injuries()
            ->where('season', $season)
            ->where('is_active', true)
            ->latest()
            ->first();
    }

    /**
     * Get positions as an array.
     */
    public function getPositionsArrayAttribute(): array
    {
        return explode(',', $this->positions);
    }

    /**
     * Check if player is eligible for a position.
     */
    public function isEligibleFor(string $position): bool
    {
        if ($position === 'UTIL' && !$this->is_pitcher) {
            return true;
        }

        if ($position === 'P' && $this->is_pitcher) {
            return true;
        }

        return in_array($position, $this->getPositionsArrayAttribute());
    }

    /**
     * Get the best ranking for this player.
     */
    public function getBestRanking(string $source = 'fantasypros_hitters', int $season = 2025)
    {
        return $this->rankings()
            ->where('source', $source)
            ->where('season', $season)
            ->orderBy('overall_rank')
            ->first();
    }

    /**
     * Get the ADP ranking for this player.
     */
    public function adpRanking(): HasOne
    {
        return $this->hasOne(PlayerRanking::class)
            ->where('source', 'fantasypros_adp')
            ->where('season', 2025);
    }

    /**
     * Get the player's ADP value.
     */
    public function getAdpAttribute(): ?float
    {
        $ranking = $this->adpRanking;
        return $ranking ? (float) $ranking->adp : null;
    }

    /**
     * Get the latest projection for this player.
     */
    public function getLatestProjection(string $source = 'fantasypros', int $season = 2025)
    {
        return $this->projections()
            ->where('source', $source)
            ->where('season', $season)
            ->latest('imported_at')
            ->first();
    }
}

