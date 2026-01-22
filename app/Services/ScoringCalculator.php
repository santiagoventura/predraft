<?php

namespace App\Services;

use App\Models\League;
use App\Models\Player;
use App\Models\PlayerProjection;
use App\Models\PlayerScore;
use App\Models\LeagueScoringCategory;
use Illuminate\Support\Facades\DB;

class ScoringCalculator
{
    /**
     * Calculate scores for all players in a league.
     */
    public function calculateLeagueScores(League $league, int $season = 2026, string $projectionSource = 'fantasypros'): int
    {
        // IMPORTANT: Always reload scoring categories to ensure we use the latest formula
        // This prevents using cached/stale scoring data when formulas are updated
        $league->load(['batterScoringCategories', 'pitcherScoringCategories']);

        $batterCategories = $league->batterScoringCategories;
        $pitcherCategories = $league->pitcherScoringCategories;

        if ($batterCategories->isEmpty() && $pitcherCategories->isEmpty()) {
            throw new \Exception('League has no scoring categories defined.');
        }

        $players = Player::with(['projections' => function ($query) use ($season, $projectionSource) {
            $query->where('season', $season)
                  ->where('source', $projectionSource);
        }])->get();

        $scoresCalculated = 0;

        DB::beginTransaction();
        try {
            foreach ($players as $player) {
                $projection = $player->projections->first();
                
                if (!$projection) {
                    continue;
                }

                $score = $this->calculatePlayerScore($player, $league, $projection, $batterCategories, $pitcherCategories);
                
                if ($score !== null) {
                    $scoresCalculated++;
                }
            }

            DB::commit();
            return $scoresCalculated;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Calculate score for a single player.
     */
    public function calculatePlayerScore(
        Player $player,
        League $league,
        PlayerProjection $projection,
        $batterCategories = null,
        $pitcherCategories = null
    ): ?PlayerScore {
        // Determine if player is a batter or pitcher
        $isBatter = $this->isBatter($player);
        $isPitcher = $this->isPitcher($player);

        if (!$isBatter && !$isPitcher) {
            return null;
        }

        // Load categories if not provided
        if ($batterCategories === null) {
            $batterCategories = $league->batterScoringCategories;
        }
        if ($pitcherCategories === null) {
            $pitcherCategories = $league->pitcherScoringCategories;
        }

        $totalPoints = 0;
        $breakdown = [];

        // Calculate batter points
        if ($isBatter && $batterCategories->isNotEmpty()) {
            foreach ($batterCategories as $category) {
                $statValue = $this->getStatValue($projection, $category->stat_code, 'batter');
                if ($statValue !== null) {
                    $points = $statValue * $category->points_per_unit;
                    $totalPoints += $points;
                    $breakdown['batter'][$category->stat_code] = [
                        'stat_name' => $category->stat_name,
                        'value' => $statValue,
                        'points_per_unit' => (float) $category->points_per_unit,
                        'points' => round($points, 2),
                    ];
                }
            }
        }

        // Calculate pitcher points
        if ($isPitcher && $pitcherCategories->isNotEmpty()) {
            foreach ($pitcherCategories as $category) {
                $statValue = $this->getStatValue($projection, $category->stat_code, 'pitcher');
                if ($statValue !== null) {
                    $points = $statValue * $category->points_per_unit;
                    $totalPoints += $points;
                    $breakdown['pitcher'][$category->stat_code] = [
                        'stat_name' => $category->stat_name,
                        'value' => $statValue,
                        'points_per_unit' => (float) $category->points_per_unit,
                        'points' => round($points, 2),
                    ];
                }
            }
        }

        // Save or update the score
        return PlayerScore::updateOrCreate(
            [
                'player_id' => $player->id,
                'league_id' => $league->id,
                'season' => $projection->season,
                'projection_source' => $projection->source,
            ],
            [
                'total_points' => round($totalPoints, 2),
                'category_breakdown' => $breakdown,
                'calculated_at' => now(),
            ]
        );
    }

    /**
     * Map stat codes to projection field names.
     */
    protected function getStatMap(string $playerType): array
    {
        if ($playerType === 'batter') {
            return [
                'AB' => 'ab',
                'PA' => 'pa',
                'H' => 'h', // Total hits - mapped to h field
                '1B' => 'singles', // Will be calculated
                '2B' => 'doubles',
                '3B' => 'triples',
                'HR' => 'hr',
                'R' => 'r',
                'RBI' => 'rbi',
                'SB' => 'sb',
                'CS' => 'cs',
                'BB' => 'bb',
                'K' => 'k',  // Strikeouts
                'SO' => 'k', // Strikeouts (alternate name - same as K)
                'HBP' => 'hbp',
                'AVG' => 'avg',
                'OBP' => 'obp',
                'SLG' => 'slg',
                'OPS' => 'ops',
            ];
        } else {
            return [
                'IP' => 'ip',
                'W' => 'w',
                'L' => 'l',
                'SV' => 'sv',
                'HLD' => 'hld',
                'K' => 'k',  // Strikeouts
                'SO' => 'k', // Strikeouts (alternate name - same as K)
                'BB' => 'bb',
                'H' => 'h',
                'ER' => 'er',
                'QS' => 'qs', // Quality Starts
                'CG' => 'cg',
                'SHO' => 'shutouts', // Shutouts (fixed from SO)
                'NH' => 'no_hitters',
                'PG' => 'perfect_games',
                'HBP' => 'hbp',
                'ERA' => 'era',
                'WHIP' => 'whip',
            ];
        }
    }

    /**
     * Get stat value from projection with special handling for calculated stats.
     */
    protected function getStatValue(PlayerProjection $projection, string $statCode, string $playerType): ?float
    {
        // Special handling for singles (1B) - calculated as H - 2B - 3B - HR
        if ($playerType === 'batter' && $statCode === '1B') {
            $hits = $projection->h ?? 0;
            $doubles = $projection->doubles ?? 0;
            $triples = $projection->triples ?? 0;
            $hr = $projection->hr ?? 0;

            $singles = $hits - $doubles - $triples - $hr;
            return max(0, $singles); // Ensure non-negative
        }

        // For 'H' stat code (total hits), return the actual hits value
        if ($playerType === 'batter' && $statCode === 'H') {
            return (float) ($projection->h ?? 0);
        }

        $statMap = $this->getStatMap($playerType);
        $field = $statMap[$statCode] ?? strtolower($statCode);

        return $projection->$field ?? null;
    }

    /**
     * Determine if player is a batter.
     */
    protected function isBatter(Player $player): bool
    {
        // Use is_pitcher flag - if not a pitcher, they're a batter
        return !$player->is_pitcher;
    }

    /**
     * Determine if player is a pitcher.
     */
    protected function isPitcher(Player $player): bool
    {
        // Use is_pitcher flag from player model
        return $player->is_pitcher;
    }

    /**
     * Get top players by score for a league.
     */
    public function getTopPlayers(League $league, int $limit = 100, int $season = 2026, string $projectionSource = 'fantasypros')
    {
        return PlayerScore::with('player')
            ->where('league_id', $league->id)
            ->where('season', $season)
            ->where('projection_source', $projectionSource)
            ->orderBy('total_points', 'desc')
            ->limit($limit)
            ->get();
    }
}

