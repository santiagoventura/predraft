<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class League extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'num_teams',
        'scoring_format',
        'scoring_categories',
        'settings',
        'description',
    ];

    protected $casts = [
        'scoring_categories' => 'array',
        'settings' => 'array',
    ];

    /**
     * Get the positions for this league.
     */
    public function positions(): HasMany
    {
        return $this->hasMany(LeaguePosition::class)->orderBy('display_order');
    }

    /**
     * Get the teams in this league.
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class)->orderBy('draft_slot');
    }

    /**
     * Get the drafts for this league.
     */
    public function drafts(): HasMany
    {
        return $this->hasMany(Draft::class);
    }

    /**
     * Get the scoring categories for this league.
     */
    public function scoringCategories(): HasMany
    {
        return $this->hasMany(LeagueScoringCategory::class)->orderBy('player_type')->orderBy('display_order');
    }

    /**
     * Get batter scoring categories.
     */
    public function batterScoringCategories(): HasMany
    {
        return $this->hasMany(LeagueScoringCategory::class)
            ->where('player_type', 'batter')
            ->where('is_active', true)
            ->orderBy('display_order');
    }

    /**
     * Get pitcher scoring categories.
     */
    public function pitcherScoringCategories(): HasMany
    {
        return $this->hasMany(LeagueScoringCategory::class)
            ->where('player_type', 'pitcher')
            ->where('is_active', true)
            ->orderBy('display_order');
    }

    /**
     * Get player scores for this league.
     */
    public function playerScores(): HasMany
    {
        return $this->hasMany(PlayerScore::class);
    }

    /**
     * Calculate total roster spots based on positions.
     */
    public function getTotalRosterSpotsAttribute(): int
    {
        return $this->positions()->sum('slot_count');
    }

    /**
     * Get the default roster configuration.
     */
    public static function getDefaultRosterConfig(): array
    {
        return [
            ['position_code' => 'C', 'slot_count' => 1, 'display_order' => 1],
            ['position_code' => '1B', 'slot_count' => 1, 'display_order' => 2],
            ['position_code' => '2B', 'slot_count' => 1, 'display_order' => 3],
            ['position_code' => 'SS', 'slot_count' => 1, 'display_order' => 4],
            ['position_code' => '3B', 'slot_count' => 1, 'display_order' => 5],
            ['position_code' => 'OF', 'slot_count' => 3, 'display_order' => 6],
            ['position_code' => 'UTIL', 'slot_count' => 3, 'display_order' => 7],
            ['position_code' => 'P', 'slot_count' => 11, 'display_order' => 8],
        ];
    }
}

