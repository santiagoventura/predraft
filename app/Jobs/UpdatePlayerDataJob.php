<?php

namespace App\Jobs;

use App\Models\League;
use App\Models\Player;
use App\Models\PlayerInjury;
use App\Models\PlayerProjection;
use App\Services\FantasyProsScraperService;
use App\Services\ScoringCalculator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UpdatePlayerDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes

    protected int $season;

    public function __construct(int $season = 2026)
    {
        $this->season = $season;
    }

    public function handle(
        FantasyProsScraperService $scraper,
        ScoringCalculator $calculator
    ): void {
        try {
            $this->updateProgress('Starting update...', 0);

            // Step 1: Fetch batter projections
            $this->updateProgress('Fetching batter projections from FanGraphs...', 10);
            $batterStats = ['imported' => 0, 'updated' => 0, 'total' => 0];
            try {
                $result = $scraper->fetchAndImportProjections('batters', $this->season);
                $batterStats = [
                    'imported' => $result['imported'] ?? 0,
                    'updated' => $result['updated'] ?? 0,
                    'total' => ($result['imported'] ?? 0) + ($result['updated'] ?? 0),
                ];
                $this->updateProgress("Batters: {$batterStats['total']} processed ({$batterStats['imported']} new, {$batterStats['updated']} updated)", 30);
            } catch (\Exception $e) {
                Log::warning('Failed to fetch batters: ' . $e->getMessage());
                $this->updateProgress('Batter fetch failed (using existing data)', 30);
            }

            // Step 2: Fetch pitcher projections
            $this->updateProgress('Fetching pitcher projections from FanGraphs...', 40);
            $pitcherStats = ['imported' => 0, 'updated' => 0, 'total' => 0];
            try {
                $result = $scraper->fetchAndImportProjections('pitchers', $this->season);
                $pitcherStats = [
                    'imported' => $result['imported'] ?? 0,
                    'updated' => $result['updated'] ?? 0,
                    'total' => ($result['imported'] ?? 0) + ($result['updated'] ?? 0),
                ];
                $this->updateProgress("Pitchers: {$pitcherStats['total']} processed ({$pitcherStats['imported']} new, {$pitcherStats['updated']} updated)", 70);
            } catch (\Exception $e) {
                Log::warning('Failed to fetch pitchers: ' . $e->getMessage());
                $this->updateProgress('Pitcher fetch failed (using existing data)', 70);
            }

            // Step 3: Calculate scores for all leagues
            $this->updateProgress('Calculating fantasy scores...', 80);
            $leagues = League::all();
            $totalScored = 0;

            foreach ($leagues as $league) {
                try {
                    // IMPORTANT: Fresh load the league to get updated scoring categories
                    // This ensures we use the latest scoring formula from the database
                    $league->refresh();
                    $league->load(['batterScoringCategories', 'pitcherScoringCategories']);

                    // Try multiple sources in order of preference
                    $sources = ['fangraphs', 'fantasypros', 'manual'];
                    $scored = 0;

                    foreach ($sources as $source) {
                        $count = PlayerProjection::where('season', $this->season)
                            ->where('source', $source)
                            ->count();

                        if ($count > 0) {
                            $scored = $calculator->calculateLeagueScores($league, $this->season, $source);
                            Log::info("Calculated {$scored} scores for {$league->name} using {$source} source");
                            break;
                        }
                    }

                    $totalScored += $scored;
                } catch (\Exception $e) {
                    Log::warning("Failed to calculate scores for {$league->name}: " . $e->getMessage());
                }
            }

            $this->updateProgress("Complete! Scored {$totalScored} players across {$leagues->count()} leagues", 100);

            // Store final stats
            Cache::put('player_data_update_stats', [
                'players' => Player::count(),
                'projections' => PlayerProjection::where('season', $this->season)->count(),
                'injuries' => PlayerInjury::where('season', $this->season)->where('is_active', true)->count(),
                'scores' => $totalScored,
                'last_updated' => now()->toDateTimeString(),
            ], now()->addDays(7));

        } catch (\Exception $e) {
            Log::error('UpdatePlayerDataJob failed: ' . $e->getMessage());
            $this->updateProgress('Update failed: ' . $e->getMessage(), -1);
            throw $e;
        }
    }

    protected function updateProgress(string $message, int $percentage): void
    {
        Cache::put('player_data_update_progress', [
            'message' => $message,
            'percentage' => $percentage,
            'updated_at' => now()->toDateTimeString(),
        ], now()->addHours(1));

        Log::info("Player Data Update: {$message} ({$percentage}%)");
    }
}

