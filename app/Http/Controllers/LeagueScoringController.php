<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Models\LeagueScoringCategory;
use App\Services\ScoringCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeagueScoringController extends Controller
{
    /**
     * Show the scoring configuration for a league.
     */
    public function index(League $league)
    {
        $league->load(['batterScoringCategories', 'pitcherScoringCategories']);
        
        return view('leagues.scoring.index', compact('league'));
    }

    /**
     * Show the form to edit scoring categories.
     */
    public function edit(League $league)
    {
        $league->load(['batterScoringCategories', 'pitcherScoringCategories']);
        
        // Get available stat options
        $batterStats = $this->getAvailableBatterStats();
        $pitcherStats = $this->getAvailablePitcherStats();
        
        return view('leagues.scoring.edit', compact('league', 'batterStats', 'pitcherStats'));
    }

    /**
     * Update the scoring categories for a league.
     */
    public function update(Request $request, League $league)
    {
        $request->validate([
            'batter_categories' => 'nullable|array',
            'batter_categories.*.stat_code' => 'required|string',
            'batter_categories.*.stat_name' => 'required|string',
            'batter_categories.*.points_per_unit' => 'required|numeric',
            'batter_categories.*.is_active' => 'boolean',
            'pitcher_categories' => 'nullable|array',
            'pitcher_categories.*.stat_code' => 'required|string',
            'pitcher_categories.*.stat_name' => 'required|string',
            'pitcher_categories.*.points_per_unit' => 'required|numeric',
            'pitcher_categories.*.is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            // Delete existing categories
            $league->scoringCategories()->delete();

            // Create batter categories
            if ($request->has('batter_categories')) {
                foreach ($request->batter_categories as $index => $category) {
                    LeagueScoringCategory::create([
                        'league_id' => $league->id,
                        'player_type' => 'batter',
                        'stat_code' => $category['stat_code'],
                        'stat_name' => $category['stat_name'],
                        'points_per_unit' => $category['points_per_unit'],
                        'display_order' => $index + 1,
                        'is_active' => $category['is_active'] ?? true,
                    ]);
                }
            }

            // Create pitcher categories
            if ($request->has('pitcher_categories')) {
                foreach ($request->pitcher_categories as $index => $category) {
                    LeagueScoringCategory::create([
                        'league_id' => $league->id,
                        'player_type' => 'pitcher',
                        'stat_code' => $category['stat_code'],
                        'stat_name' => $category['stat_name'],
                        'points_per_unit' => $category['points_per_unit'],
                        'display_order' => $index + 1,
                        'is_active' => $category['is_active'] ?? true,
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('leagues.scoring.index', $league)
                ->with('success', 'Scoring categories updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update scoring categories: ' . $e->getMessage()]);
        }
    }

    /**
     * Apply a preset scoring configuration.
     */
    public function applyPreset(Request $request, League $league)
    {
        $request->validate([
            'preset' => 'required|in:yahoo,espn,cbs,default',
        ]);

        DB::beginTransaction();
        try {
            // Delete existing categories
            $league->scoringCategories()->delete();

            [$batterCategories, $pitcherCategories] = $this->getPresetCategories($request->preset);

            // Create batter categories
            foreach ($batterCategories as $category) {
                LeagueScoringCategory::create([
                    'league_id' => $league->id,
                    'player_type' => 'batter',
                    ...$category,
                ]);
            }

            // Create pitcher categories
            foreach ($pitcherCategories as $category) {
                LeagueScoringCategory::create([
                    'league_id' => $league->id,
                    'player_type' => 'pitcher',
                    ...$category,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('leagues.scoring.index', $league)
                ->with('success', ucfirst($request->preset) . ' preset applied successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to apply preset: ' . $e->getMessage()]);
        }
    }

    /**
     * Calculate player scores for this league.
     */
    public function calculateScores(Request $request, League $league, ScoringCalculator $calculator)
    {
        $request->validate([
            'season' => 'nullable|integer|min:2020|max:2030',
            'source' => 'nullable|string',
        ]);

        $season = $request->input('season', 2025);
        $source = $request->input('source', 'fantasypros');

        try {
            $count = $calculator->calculateLeagueScores($league, $season, $source);

            return redirect()
                ->route('leagues.scoring.index', $league)
                ->with('success', "Calculated scores for {$count} players!");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to calculate scores: ' . $e->getMessage()]);
        }
    }

    /**
     * Get available batter stats.
     */
    protected function getAvailableBatterStats(): array
    {
        return [
            'H' => 'Singles (1B)',
            '2B' => 'Doubles',
            '3B' => 'Triples',
            'HR' => 'Home Runs',
            'R' => 'Runs',
            'RBI' => 'RBI',
            'SB' => 'Stolen Bases',
            'CS' => 'Caught Stealing',
            'BB' => 'Walks',
            'K' => 'Strikeouts',
            'HBP' => 'Hit By Pitch',
            'AVG' => 'Batting Average',
            'OBP' => 'On-Base Percentage',
            'SLG' => 'Slugging Percentage',
            'OPS' => 'OPS',
        ];
    }

    /**
     * Get available pitcher stats.
     */
    protected function getAvailablePitcherStats(): array
    {
        return [
            'IP' => 'Innings Pitched',
            'W' => 'Wins',
            'L' => 'Losses',
            'SV' => 'Saves',
            'HLD' => 'Holds',
            'K' => 'Strikeouts',
            'BB' => 'Walks Allowed',
            'H' => 'Hits Allowed',
            'ER' => 'Earned Runs',
            'QS' => 'Quality Starts',
            'CG' => 'Complete Games',
            'SO' => 'Shutouts',
            'NH' => 'No Hitters',
            'PG' => 'Perfect Games',
            'HBP' => 'Hit Batsmen',
            'ERA' => 'ERA',
            'WHIP' => 'WHIP',
        ];
    }

    /**
     * Get preset scoring categories.
     */
    protected function getPresetCategories(string $preset): array
    {
        return match ($preset) {
            'yahoo' => [
                LeagueScoringCategory::getDefaultBatterCategories(),
                LeagueScoringCategory::getDefaultPitcherCategories(),
            ],
            'espn' => $this->getESPNPreset(),
            'cbs' => $this->getCBSPreset(),
            'default' => [
                LeagueScoringCategory::getDefaultBatterCategories(),
                LeagueScoringCategory::getDefaultPitcherCategories(),
            ],
        };
    }

    /**
     * Get ESPN preset.
     */
    protected function getESPNPreset(): array
    {
        $batters = [
            ['stat_code' => 'H', 'stat_name' => 'Singles (1B)', 'points_per_unit' => 1, 'display_order' => 1],
            ['stat_code' => '2B', 'stat_name' => 'Doubles', 'points_per_unit' => 2, 'display_order' => 2],
            ['stat_code' => '3B', 'stat_name' => 'Triples', 'points_per_unit' => 3, 'display_order' => 3],
            ['stat_code' => 'HR', 'stat_name' => 'Home Runs', 'points_per_unit' => 4, 'display_order' => 4],
            ['stat_code' => 'R', 'stat_name' => 'Runs', 'points_per_unit' => 1, 'display_order' => 5],
            ['stat_code' => 'RBI', 'stat_name' => 'RBI', 'points_per_unit' => 1, 'display_order' => 6],
            ['stat_code' => 'SB', 'stat_name' => 'Stolen Bases', 'points_per_unit' => 2, 'display_order' => 7],
            ['stat_code' => 'BB', 'stat_name' => 'Walks', 'points_per_unit' => 1, 'display_order' => 8],
        ];

        $pitchers = [
            ['stat_code' => 'IP', 'stat_name' => 'Innings Pitched', 'points_per_unit' => 3, 'display_order' => 1],
            ['stat_code' => 'W', 'stat_name' => 'Wins', 'points_per_unit' => 5, 'display_order' => 2],
            ['stat_code' => 'SV', 'stat_name' => 'Saves', 'points_per_unit' => 5, 'display_order' => 3],
            ['stat_code' => 'K', 'stat_name' => 'Strikeouts', 'points_per_unit' => 1, 'display_order' => 4],
            ['stat_code' => 'H', 'stat_name' => 'Hits Allowed', 'points_per_unit' => -1, 'display_order' => 5],
            ['stat_code' => 'BB', 'stat_name' => 'Walks Allowed', 'points_per_unit' => -1, 'display_order' => 6],
            ['stat_code' => 'ER', 'stat_name' => 'Earned Runs', 'points_per_unit' => -2, 'display_order' => 7],
        ];

        return [$batters, $pitchers];
    }

    /**
     * Get CBS preset.
     */
    protected function getCBSPreset(): array
    {
        $batters = [
            ['stat_code' => 'H', 'stat_name' => 'Singles (1B)', 'points_per_unit' => 1, 'display_order' => 1],
            ['stat_code' => '2B', 'stat_name' => 'Doubles', 'points_per_unit' => 2, 'display_order' => 2],
            ['stat_code' => '3B', 'stat_name' => 'Triples', 'points_per_unit' => 3, 'display_order' => 3],
            ['stat_code' => 'HR', 'stat_name' => 'Home Runs', 'points_per_unit' => 4, 'display_order' => 4],
            ['stat_code' => 'R', 'stat_name' => 'Runs', 'points_per_unit' => 1, 'display_order' => 5],
            ['stat_code' => 'RBI', 'stat_name' => 'RBI', 'points_per_unit' => 1, 'display_order' => 6],
            ['stat_code' => 'SB', 'stat_name' => 'Stolen Bases', 'points_per_unit' => 2, 'display_order' => 7],
            ['stat_code' => 'BB', 'stat_name' => 'Walks', 'points_per_unit' => 1, 'display_order' => 8],
            ['stat_code' => 'K', 'stat_name' => 'Strikeouts', 'points_per_unit' => -0.5, 'display_order' => 9],
        ];

        $pitchers = [
            ['stat_code' => 'IP', 'stat_name' => 'Innings Pitched', 'points_per_unit' => 3, 'display_order' => 1],
            ['stat_code' => 'W', 'stat_name' => 'Wins', 'points_per_unit' => 5, 'display_order' => 2],
            ['stat_code' => 'L', 'stat_name' => 'Losses', 'points_per_unit' => -3, 'display_order' => 3],
            ['stat_code' => 'SV', 'stat_name' => 'Saves', 'points_per_unit' => 7, 'display_order' => 4],
            ['stat_code' => 'K', 'stat_name' => 'Strikeouts', 'points_per_unit' => 1, 'display_order' => 5],
            ['stat_code' => 'H', 'stat_name' => 'Hits Allowed', 'points_per_unit' => -1, 'display_order' => 6],
            ['stat_code' => 'BB', 'stat_name' => 'Walks Allowed', 'points_per_unit' => -1, 'display_order' => 7],
            ['stat_code' => 'ER', 'stat_name' => 'Earned Runs', 'points_per_unit' => -2, 'display_order' => 8],
        ];

        return [$batters, $pitchers];
    }
}

