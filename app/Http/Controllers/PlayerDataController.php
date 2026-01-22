<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Models\Player;
use App\Models\PlayerProjection;
use App\Models\PlayerScore;
use App\Models\PlayerInjury;
use App\Services\FantasyProsImporter;
use App\Services\FantasyProsScraperService;
use App\Services\ScoringCalculator;
use App\Services\InjuryDataService;
use App\Jobs\UpdatePlayerDataJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PlayerDataController extends Controller
{
    public function __construct(
        protected FantasyProsImporter $importer,
        protected FantasyProsScraperService $scraper,
        protected ScoringCalculator $calculator,
        protected InjuryDataService $injuryService
    ) {}

    /**
     * Show the player data management page.
     */
    public function index()
    {
        $season = 2026;

        $stats = [
            'total_players' => Player::count(),
            'batters' => Player::where('is_pitcher', false)->count(),
            'pitchers' => Player::where('is_pitcher', true)->count(),
            'projections_2026' => PlayerProjection::where('season', $season)->count(),
            'injuries_active' => PlayerInjury::where('season', $season)->where('is_active', true)->count(),
            'scores_2026' => PlayerScore::where('season', $season)->count(),
            'leagues' => League::count(),
        ];

        // Get update progress
        $progress = Cache::get('player_data_update_progress');
        $updateStats = Cache::get('player_data_update_stats');

        // Get last update date from database if not in cache
        $lastUpdated = null;
        if ($updateStats && isset($updateStats['last_updated'])) {
            $lastUpdated = $updateStats['last_updated'];
        } else {
            // Fallback: get the most recent projection imported_at date
            $latestProjection = PlayerProjection::where('season', $season)
                ->orderByDesc('imported_at')
                ->first();
            if ($latestProjection && $latestProjection->imported_at) {
                $lastUpdated = $latestProjection->imported_at->format('Y-m-d H:i:s');
            }
        }

        return view('admin.player-data.index', compact('stats', 'progress', 'updateStats', 'lastUpdated'));
    }

    /**
     * Start the background update job.
     */
    public function startUpdate(Request $request)
    {
        $season = $request->input('season', 2026);

        // Dispatch the job to run in background
        UpdatePlayerDataJob::dispatch($season);

        return redirect()->route('admin.player-data.index')
            ->with('success', 'Update started! The system is fetching data in the background. Refresh this page to see progress.');
    }

    /**
     * Get update progress (AJAX endpoint).
     */
    public function getProgress()
    {
        $progress = Cache::get('player_data_update_progress', [
            'message' => 'No update in progress',
            'percentage' => 0,
            'updated_at' => null,
        ]);

        $stats = Cache::get('player_data_update_stats');

        return response()->json([
            'progress' => $progress,
            'stats' => $stats,
        ]);
    }

    /**
     * Fetch projections from FantasyPros using AI.
     */
    public function fetchFromFantasyPros(Request $request)
    {
        $request->validate([
            'player_type' => 'required|in:batters,pitchers',
            'season' => 'required|integer|min:2020|max:2030',
        ]);

        try {
            $playerType = $request->input('player_type');
            $season = $request->input('season');

            // Fetch and import using AI scraper
            $result = $this->scraper->fetchAndImportProjections($playerType, $season);

            $message = "✅ Fetched from FantasyPros: {$result['imported']} new projections, {$result['updated']} updated";

            if (!empty($result['created_players'])) {
                $message .= " (Created " . count($result['created_players']) . " new players)";
            }

            return redirect()->route('admin.player-data.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Error fetching from FantasyPros', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Fetch failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Fetch injury data from external sources.
     */
    public function fetchInjuries(Request $request)
    {
        try {
            $injuries = $this->injuryService->fetchInjuryData();

            $count = count($injuries);

            return redirect()->route('admin.player-data.index')
                ->with('success', "Fetched injury data for {$count} players. Data cached for 6 hours.");
        } catch (\Exception $e) {
            Log::error('Error fetching injury data', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Fetch failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Import player projections from CSV (backup method).
     */
    public function importProjections(Request $request)
    {
        $request->validate([
            'projections_file' => 'required|file|mimes:csv,txt',
            'season' => 'required|integer|min:2020|max:2030',
            'source' => 'required|string|max:50',
        ]);

        try {
            $file = $request->file('projections_file');
            $season = $request->input('season');
            $source = $request->input('source');

            // Move file to temp location
            $path = $file->storeAs('temp', 'projections_' . time() . '.csv');
            $fullPath = storage_path('app/' . $path);

            // Import projections
            $result = $this->importer->importProjectionsFromCsv($fullPath, $source, $season);

            // Clean up
            unlink($fullPath);

            return redirect()->route('admin.player-data.index')
                ->with('success', "Imported {$result['imported']} projections, updated {$result['updated']}");
        } catch (\Exception $e) {
            Log::error('Error importing projections', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Import failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Calculate scores for a league.
     */
    public function calculateScores(Request $request)
    {
        $request->validate([
            'league_id' => 'required|exists:leagues,id',
            'season' => 'required|integer|min:2020|max:2030',
            'source' => 'required|string|max:50',
        ]);

        try {
            $league = League::findOrFail($request->input('league_id'));
            $season = $request->input('season');
            $source = $request->input('source');

            $count = $this->calculator->calculateLeagueScores($league, $season, $source);

            return redirect()->route('admin.player-data.index')
                ->with('success', "Calculated scores for {$count} players in {$league->name}");
        } catch (\Exception $e) {
            Log::error('Error calculating scores', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Calculation failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Calculate scores for all leagues.
     */
    public function calculateAllScores(Request $request)
    {
        $request->validate([
            'season' => 'required|integer|min:2020|max:2030',
            'source' => 'required|string|max:50',
        ]);

        try {
            $season = $request->input('season');
            $source = $request->input('source');
            $leagues = League::all();
            $totalCount = 0;

            foreach ($leagues as $league) {
                $count = $this->calculator->calculateLeagueScores($league, $season, $source);
                $totalCount += $count;
            }

            return redirect()->route('admin.player-data.index')
                ->with('success', "Calculated scores for {$totalCount} players across {$leagues->count()} leagues");
        } catch (\Exception $e) {
            Log::error('Error calculating all scores', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Calculation failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Clear all projections.
     */
    public function clearProjections(Request $request)
    {
        $request->validate([
            'confirm' => 'required|accepted',
        ]);

        try {
            $count = PlayerProjection::count();
            PlayerProjection::truncate();

            return redirect()->route('admin.player-data.index')
                ->with('success', "Deleted {$count} projections");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to clear projections: ' . $e->getMessage()]);
        }
    }

    /**
     * Clear all calculated scores.
     */
    public function clearScores(Request $request)
    {
        $request->validate([
            'confirm' => 'required|accepted',
        ]);

        try {
            $count = PlayerScore::count();
            PlayerScore::truncate();

            return redirect()->route('admin.player-data.index')
                ->with('success', "Deleted {$count} calculated scores");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to clear scores: ' . $e->getMessage()]);
        }
    }
}

