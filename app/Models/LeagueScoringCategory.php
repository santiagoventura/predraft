<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeagueScoringCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'league_id',
        'player_type',
        'stat_code',
        'stat_name',
        'points_per_unit',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'points_per_unit' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    /**
     * Get the league that owns this scoring category.
     */
    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    /**
     * Get default scoring categories for batters (Yahoo MLB Fantasy).
     */
    public static function getDefaultBatterCategories(): array
    {
        return [
            ['stat_code' => 'H', 'stat_name' => 'Singles (1B)', 'points_per_unit' => 2.6, 'display_order' => 1],
            ['stat_code' => '2B', 'stat_name' => 'Doubles', 'points_per_unit' => 5.2, 'display_order' => 2],
            ['stat_code' => '3B', 'stat_name' => 'Triples', 'points_per_unit' => 7.8, 'display_order' => 3],
            ['stat_code' => 'HR', 'stat_name' => 'Home Runs', 'points_per_unit' => 10.4, 'display_order' => 4],
            ['stat_code' => 'R', 'stat_name' => 'Runs', 'points_per_unit' => 1.9, 'display_order' => 5],
            ['stat_code' => 'RBI', 'stat_name' => 'RBI', 'points_per_unit' => 1.9, 'display_order' => 6],
            ['stat_code' => 'SB', 'stat_name' => 'Stolen Bases', 'points_per_unit' => 4.2, 'display_order' => 7],
            ['stat_code' => 'BB', 'stat_name' => 'Walks', 'points_per_unit' => 2.6, 'display_order' => 8],
            ['stat_code' => 'HBP', 'stat_name' => 'Hit By Pitch', 'points_per_unit' => 2.6, 'display_order' => 9],
            ['stat_code' => 'CS', 'stat_name' => 'Caught Stealing', 'points_per_unit' => -2.8, 'display_order' => 10],
            ['stat_code' => 'K', 'stat_name' => 'Strikeouts', 'points_per_unit' => -1, 'display_order' => 11],
        ];
    }

    /**
     * Get default scoring categories for pitchers (Yahoo MLB Fantasy).
     */
    public static function getDefaultPitcherCategories(): array
    {
        return [
            ['stat_code' => 'IP', 'stat_name' => 'Innings Pitched', 'points_per_unit' => 7.4, 'display_order' => 1],
            ['stat_code' => 'W', 'stat_name' => 'Wins', 'points_per_unit' => 4.3, 'display_order' => 2],
            ['stat_code' => 'L', 'stat_name' => 'Losses', 'points_per_unit' => -2.6, 'display_order' => 3],
            ['stat_code' => 'CG', 'stat_name' => 'Complete Games', 'points_per_unit' => 2.6, 'display_order' => 4],
            ['stat_code' => 'SO', 'stat_name' => 'Shutouts', 'points_per_unit' => 5, 'display_order' => 5],
            ['stat_code' => 'SV', 'stat_name' => 'Saves', 'points_per_unit' => 5, 'display_order' => 6],
            ['stat_code' => 'K', 'stat_name' => 'Strikeouts', 'points_per_unit' => 2, 'display_order' => 7],
            ['stat_code' => 'H', 'stat_name' => 'Hits Allowed', 'points_per_unit' => -2.6, 'display_order' => 8],
            ['stat_code' => 'ER', 'stat_name' => 'Earned Runs', 'points_per_unit' => -3.2, 'display_order' => 9],
            ['stat_code' => 'BB', 'stat_name' => 'Walks Allowed', 'points_per_unit' => -2.6, 'display_order' => 10],
            ['stat_code' => 'HBP', 'stat_name' => 'Hit Batsmen', 'points_per_unit' => -2.6, 'display_order' => 11],
            ['stat_code' => 'NH', 'stat_name' => 'No Hitters', 'points_per_unit' => 25, 'display_order' => 12],
            ['stat_code' => 'PG', 'stat_name' => 'Perfect Games', 'points_per_unit' => 25, 'display_order' => 13],
        ];
    }
}

