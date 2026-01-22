<?php

namespace App\Console\Commands;

use App\Models\League;
use App\Models\LeagueScoringCategory;
use Illuminate\Console\Command;

class SetupLeagueScoring extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'league:setup-scoring 
                            {league_id : The ID of the league}
                            {--preset=default : The scoring preset to use (default, espn, yahoo, custom)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup scoring categories for a league';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $leagueId = $this->argument('league_id');
        $preset = $this->option('preset');

        $league = League::find($leagueId);

        if (!$league) {
            $this->error("League with ID {$leagueId} not found.");
            return 1;
        }

        $this->info("Setting up scoring categories for league: {$league->name}");

        // Delete existing categories
        $league->scoringCategories()->delete();

        // Get preset categories
        [$batterCategories, $pitcherCategories] = $this->getPresetCategories($preset);

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

        $this->info("✓ Created {$league->scoringCategories()->count()} scoring categories");
        $this->info("  - Batter categories: " . count($batterCategories));
        $this->info("  - Pitcher categories: " . count($pitcherCategories));

        return 0;
    }

    /**
     * Get preset scoring categories.
     */
    protected function getPresetCategories(string $preset): array
    {
        return match ($preset) {
            'espn' => $this->getESPNPreset(),
            'yahoo' => $this->getYahooPreset(),
            'default' => $this->getDefaultPreset(),
            default => $this->getDefaultPreset(),
        };
    }

    /**
     * Get default scoring preset.
     */
    protected function getDefaultPreset(): array
    {
        return [
            LeagueScoringCategory::getDefaultBatterCategories(),
            LeagueScoringCategory::getDefaultPitcherCategories(),
        ];
    }

    /**
     * Get ESPN-style scoring preset.
     */
    protected function getESPNPreset(): array
    {
        $batters = [
            ['stat_code' => 'H', 'stat_name' => 'Hits', 'points_per_unit' => 1, 'display_order' => 1],
            ['stat_code' => '2B', 'stat_name' => 'Doubles', 'points_per_unit' => 1, 'display_order' => 2],
            ['stat_code' => '3B', 'stat_name' => 'Triples', 'points_per_unit' => 2, 'display_order' => 3],
            ['stat_code' => 'HR', 'stat_name' => 'Home Runs', 'points_per_unit' => 4, 'display_order' => 4],
            ['stat_code' => 'R', 'stat_name' => 'Runs', 'points_per_unit' => 1, 'display_order' => 5],
            ['stat_code' => 'RBI', 'stat_name' => 'RBI', 'points_per_unit' => 1, 'display_order' => 6],
            ['stat_code' => 'SB', 'stat_name' => 'Stolen Bases', 'points_per_unit' => 1, 'display_order' => 7],
            ['stat_code' => 'BB', 'stat_name' => 'Walks', 'points_per_unit' => 1, 'display_order' => 8],
        ];

        $pitchers = [
            ['stat_code' => 'IP', 'stat_name' => 'Innings Pitched', 'points_per_unit' => 3, 'display_order' => 1],
            ['stat_code' => 'W', 'stat_name' => 'Wins', 'points_per_unit' => 3, 'display_order' => 2],
            ['stat_code' => 'SV', 'stat_name' => 'Saves', 'points_per_unit' => 5, 'display_order' => 3],
            ['stat_code' => 'K', 'stat_name' => 'Strikeouts', 'points_per_unit' => 1, 'display_order' => 4],
            ['stat_code' => 'H', 'stat_name' => 'Hits Allowed', 'points_per_unit' => -1, 'display_order' => 5],
            ['stat_code' => 'BB', 'stat_name' => 'Walks', 'points_per_unit' => -1, 'display_order' => 6],
            ['stat_code' => 'ER', 'stat_name' => 'Earned Runs', 'points_per_unit' => -2, 'display_order' => 7],
        ];

        return [$batters, $pitchers];
    }

    /**
     * Get Yahoo-style scoring preset.
     */
    protected function getYahooPreset(): array
    {
        $batters = [
            ['stat_code' => 'H', 'stat_name' => 'Hits', 'points_per_unit' => 2.6, 'display_order' => 1],
            ['stat_code' => '2B', 'stat_name' => 'Doubles', 'points_per_unit' => 2.6, 'display_order' => 2],
            ['stat_code' => '3B', 'stat_name' => 'Triples', 'points_per_unit' => 5.2, 'display_order' => 3],
            ['stat_code' => 'HR', 'stat_name' => 'Home Runs', 'points_per_unit' => 10.4, 'display_order' => 4],
            ['stat_code' => 'R', 'stat_name' => 'Runs', 'points_per_unit' => 1.9, 'display_order' => 5],
            ['stat_code' => 'RBI', 'stat_name' => 'RBI', 'points_per_unit' => 1.9, 'display_order' => 6],
            ['stat_code' => 'SB', 'stat_name' => 'Stolen Bases', 'points_per_unit' => 4.2, 'display_order' => 7],
            ['stat_code' => 'BB', 'stat_name' => 'Walks', 'points_per_unit' => 2.6, 'display_order' => 8],
        ];

        $pitchers = [
            ['stat_code' => 'IP', 'stat_name' => 'Innings Pitched', 'points_per_unit' => 7.4, 'display_order' => 1],
            ['stat_code' => 'W', 'stat_name' => 'Wins', 'points_per_unit' => 4.3, 'display_order' => 2],
            ['stat_code' => 'SV', 'stat_name' => 'Saves', 'points_per_unit' => 5, 'display_order' => 3],
            ['stat_code' => 'K', 'stat_name' => 'Strikeouts', 'points_per_unit' => 2, 'display_order' => 4],
            ['stat_code' => 'H', 'stat_name' => 'Hits Allowed', 'points_per_unit' => -2.6, 'display_order' => 5],
            ['stat_code' => 'BB', 'stat_name' => 'Walks', 'points_per_unit' => -2.6, 'display_order' => 6],
            ['stat_code' => 'ER', 'stat_name' => 'Earned Runs', 'points_per_unit' => -3.2, 'display_order' => 7],
        ];

        return [$batters, $pitchers];
    }
}

