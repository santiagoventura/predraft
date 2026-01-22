<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Models\Player;
use App\Models\PlayerScore;
use Illuminate\Http\Request;

class RankingsController extends Controller
{
    /**
     * Display the player rankings with stats and points breakdown.
     */
    public function index(Request $request, League $league)
    {
        // Get filter parameters
        $position = $request->get('position', 'all');
        $playerType = $request->get('type', 'all'); // 'all', 'batter', 'pitcher'
        $search = $request->get('search', '');
        $perPage = $request->get('per_page', 50);
        $sortBy = $request->get('sort', 'points'); // 'points' or 'adp'
        $sortDir = $request->get('dir', 'desc'); // 'asc' or 'desc'

        // Build query for player scores - eager load ADP ranking
        $query = PlayerScore::with(['player.latestProjection', 'player.adpRanking'])
            ->where('player_scores.league_id', $league->id)
            ->where('player_scores.season', 2026);

        // Filter by player type
        if ($playerType === 'batter') {
            $query->whereHas('player', fn($q) => $q->where('is_pitcher', false));
        } elseif ($playerType === 'pitcher') {
            $query->whereHas('player', fn($q) => $q->where('is_pitcher', true));
        }

        // Filter by position
        if ($position !== 'all') {
            $query->whereHas('player', function ($q) use ($position) {
                $q->where('positions', 'like', "%{$position}%");
            });
        }

        // Search by player name
        if ($search) {
            $query->whereHas('player', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('mlb_team', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        if ($sortBy === 'adp') {
            // Join with player_rankings to sort by ADP
            $query->select('player_scores.*')
                ->leftJoin('player_rankings', function ($join) {
                    $join->on('player_scores.player_id', '=', 'player_rankings.player_id')
                        ->where('player_rankings.source', '=', 'fantasypros_adp')
                        ->where('player_rankings.season', '=', 2025);
                })
                ->orderByRaw('player_rankings.adp IS NULL')  // Nulls last
                ->orderBy('player_rankings.adp', $sortDir === 'desc' ? 'desc' : 'asc');
        } else {
            // Default: sort by total points
            $query->orderBy('player_scores.total_points', $sortDir === 'asc' ? 'asc' : 'desc');
        }

        // Get paginated results
        $rankings = $query->paginate($perPage)->withQueryString();

        // Get scoring categories for formula display
        $batterCategories = $league->batterScoringCategories()->get();
        $pitcherCategories = $league->pitcherScoringCategories()->get();

        // Get available positions for filter
        $positions = $this->getAvailablePositions();

        // Calculate some stats
        $totalPlayers = PlayerScore::where('league_id', $league->id)
            ->where('season', 2026)
            ->count();
        $totalBatters = PlayerScore::where('league_id', $league->id)
            ->where('season', 2026)
            ->whereHas('player', fn($q) => $q->where('is_pitcher', false))
            ->count();
        $totalPitchers = PlayerScore::where('league_id', $league->id)
            ->where('season', 2026)
            ->whereHas('player', fn($q) => $q->where('is_pitcher', true))
            ->count();

        return view('rankings.index', compact(
            'league',
            'rankings',
            'batterCategories',
            'pitcherCategories',
            'positions',
            'position',
            'playerType',
            'search',
            'perPage',
            'sortBy',
            'sortDir',
            'totalPlayers',
            'totalBatters',
            'totalPitchers'
        ));
    }

    /**
     * Get available positions for filtering.
     */
    protected function getAvailablePositions(): array
    {
        return [
            'all' => 'All Positions',
            'C' => 'Catcher (C)',
            '1B' => 'First Base (1B)',
            '2B' => 'Second Base (2B)',
            '3B' => 'Third Base (3B)',
            'SS' => 'Shortstop (SS)',
            'OF' => 'Outfield (OF)',
            'DH' => 'Designated Hitter (DH)',
            'SP' => 'Starting Pitcher (SP)',
            'RP' => 'Relief Pitcher (RP)',
        ];
    }
}

