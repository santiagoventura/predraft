<?php

namespace App\Console\Commands;

use App\Models\League;
use App\Services\ScoringCalculator;
use Illuminate\Console\Command;

class CalculatePlayerScores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scores:calculate 
                            {league_id? : The ID of the league to calculate scores for}
                            {--season=2025 : The season to calculate scores for}
                            {--source=fantasypros : The projection source to use}
                            {--all : Calculate scores for all leagues}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate player scores based on league scoring categories';

    /**
     * Execute the console command.
     */
    public function handle(ScoringCalculator $calculator): int
    {
        $season = (int) $this->option('season');
        $source = $this->option('source');

        if ($this->option('all')) {
            $leagues = League::with(['batterScoringCategories', 'pitcherScoringCategories'])->get();
            
            if ($leagues->isEmpty()) {
                $this->error('No leagues found.');
                return 1;
            }

            $this->info("Calculating scores for {$leagues->count()} leagues...");
            
            foreach ($leagues as $league) {
                $this->calculateForLeague($league, $calculator, $season, $source);
            }

            return 0;
        }

        $leagueId = $this->argument('league_id');
        
        if (!$leagueId) {
            $this->error('Please provide a league ID or use --all flag.');
            return 1;
        }

        $league = League::with(['batterScoringCategories', 'pitcherScoringCategories'])->find($leagueId);

        if (!$league) {
            $this->error("League with ID {$leagueId} not found.");
            return 1;
        }

        $this->calculateForLeague($league, $calculator, $season, $source);

        return 0;
    }

    /**
     * Calculate scores for a specific league.
     */
    protected function calculateForLeague(League $league, ScoringCalculator $calculator, int $season, string $source): void
    {
        $this->info("Calculating scores for league: {$league->name}");

        try {
            $count = $calculator->calculateLeagueScores($league, $season, $source);
            $this->info("✓ Calculated scores for {$count} players");
        } catch (\Exception $e) {
            $this->error("✗ Error: {$e->getMessage()}");
        }
    }
}

