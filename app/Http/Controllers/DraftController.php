<?php

namespace App\Http\Controllers;

use App\Models\Draft;
use App\Models\League;
use App\Models\Player;
use App\Services\DraftAIService;
use App\Services\DraftSimulator;
use Illuminate\Http\Request;

class DraftController extends Controller
{
    public function __construct(
        protected DraftSimulator $draftSimulator,
        protected DraftAIService $aiService
    ) {}

    /**
     * Display a listing of drafts.
     */
    public function index()
    {
        $drafts = Draft::with('league')->latest()->get();
        return view('drafts.index', compact('drafts'));
    }

    /**
     * Show the form for creating a new draft.
     */
    public function create(League $league)
    {
        return view('drafts.create', compact('league'));
    }

    /**
     * Store a newly created draft.
     */
    public function store(Request $request, League $league)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
        ]);

        try {
            $draft = $this->draftSimulator->initializeDraft(
                $league,
                $validated['name'] ?? null
            );

            return redirect()->route('drafts.show', $draft)
                ->with('success', 'Draft created successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to create draft: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the draft board.
     */
    public function show(Draft $draft)
    {
        $draft->load([
            'league.positions',
            'league.teams',
            'picks.player',
            'picks.team',
            'currentTeam',
        ]);

        $currentPick = $this->draftSimulator->getCurrentPick($draft);
        $summary = $this->draftSimulator->getDraftSummary($draft);

        // Get recent picks (most recent first)
        // Use reorder() to remove the default orderBy from the relationship
        $recentPicks = $draft->picks()
            ->whereNotNull('player_id')
            ->with(['player', 'team'])
            ->reorder('overall_pick', 'desc')
            ->take(10)
            ->get();

        // Get available players for manual selection with their league-specific scores
        $availablePlayers = $this->draftSimulator->getAvailablePlayers($draft);

        // Add player scores for ranking display
        $playerScores = \App\Models\PlayerScore::where('league_id', $draft->league_id)
            ->where('season', 2026)
            ->pluck('total_points', 'player_id');

        // Get ADP data for all players
        $adpData = \App\Models\PlayerRanking::where('source', 'fantasypros_adp')
            ->where('season', 2025)
            ->pluck('adp', 'player_id');

        // Add points and ADP to each player and sort by points desc
        $availablePlayers = $availablePlayers->map(function ($player) use ($playerScores, $adpData) {
            $player->points = $playerScores[$player->id] ?? 0;
            $player->adp = $adpData[$player->id] ?? null;
            return $player;
        })->sortByDesc('points')->values();

        // Prepare JSON-safe data for JavaScript (with points and ADP included)
        $availablePlayersJson = $availablePlayers->map(function ($player) {
            return [
                'id' => $player->id,
                'name' => $player->name,
                'mlb_team' => $player->mlb_team,
                'positions' => $player->positions,
                'is_pitcher' => $player->is_pitcher,
                'points' => (float) $player->points,
                'adp' => $player->adp ? (float) $player->adp : null,
            ];
        })->values();

        // Get team rosters for draft board display
        $teamRosters = [];
        foreach ($draft->league->teams as $team) {
            $teamRosters[$team->id] = $this->draftSimulator->getTeamRoster($draft, $team);
        }

        return view('drafts.show', compact('draft', 'currentPick', 'summary', 'recentPicks', 'availablePlayers', 'availablePlayersJson', 'teamRosters'));
    }

    /**
     * Start the draft.
     */
    public function start(Draft $draft)
    {
        try {
            $this->draftSimulator->startDraft($draft);

            return redirect()->route('drafts.show', $draft)
                ->with('success', 'Draft started!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to start draft: ' . $e->getMessage()]);
        }
    }

    /**
     * Get AI recommendations for current pick.
     */
    public function recommendations(Draft $draft)
    {
        if (!$draft->isInProgress()) {
            return response()->json(['error' => 'Draft is not in progress'], 400);
        }

        $currentPick = $this->draftSimulator->getCurrentPick($draft);
        
        if (!$currentPick) {
            return response()->json(['error' => 'No current pick'], 400);
        }

        try {
            $result = $this->aiService->getRecommendations($draft, $currentPick->team);
            
            if (!$result['success']) {
                // Fallback to simple recommendations
                $result = $this->aiService->getFallbackRecommendations($draft, $currentPick->team);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            // Return fallback on error
            $result = $this->aiService->getFallbackRecommendations($draft, $currentPick->team);
            return response()->json($result);
        }
    }

    /**
     * Make a pick.
     */
    public function makePick(Request $request, Draft $draft)
    {
        \Log::info('makePick called', [
            'player_id' => $request->input('player_id'),
            'position_filled' => $request->input('position_filled'),
            'expects_json' => $request->expectsJson(),
        ]);

        $validated = $request->validate([
            'player_id' => 'required|exists:players,id',
            'position_filled' => 'nullable|string', // Now optional - will auto-determine if not provided
        ]);

        try {
            $player = Player::findOrFail($validated['player_id']);
            $currentPick = $this->draftSimulator->getCurrentPick($draft);

            if (!$currentPick) {
                throw new \Exception('No current pick found');
            }

            // Auto-determine position if not provided
            $positionFilled = $validated['position_filled'] ??
                $this->draftSimulator->determinePositionToFill($draft, $currentPick->team, $player);

            \Log::info('Position determined', ['position' => $positionFilled]);

            $pick = $this->draftSimulator->makePick(
                $draft,
                $player,
                $positionFilled,
                $request->input('recommendations'),
                $request->input('ai_explanation')
            );

            \Log::info('Pick made successfully', ['pick_id' => $pick->id]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'pick' => $pick,
                    'draft' => $draft->fresh(),
                    'position_filled' => $positionFilled,
                ]);
            }

            return redirect()->route('drafts.show', $draft)
                ->with('success', "Picked {$player->name} for {$positionFilled}!");
        } catch (\Exception $e) {
            \Log::error('makePick failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 400);
            }

            return back()->withErrors(['error' => 'Failed to make pick: ' . $e->getMessage()]);
        }
    }

    /**
     * Revert the last pick.
     */
    public function revertPick(Request $request, Draft $draft)
    {
        \Log::info('revertPick called', [
            'draft_id' => $draft->id,
            'expects_json' => $request->expectsJson(),
        ]);

        try {
            $revertedPick = $this->draftSimulator->revertLastPick($draft);

            if (!$revertedPick) {
                throw new \Exception('No picks to revert');
            }

            \Log::info('Pick reverted successfully', [
                'pick_id' => $revertedPick->id,
                'overall_pick' => $revertedPick->overall_pick,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Last pick reverted successfully',
                    'draft' => $draft->fresh(),
                    'reverted_pick' => $revertedPick,
                ]);
            }

            return redirect()->route('drafts.show', $draft)
                ->with('success', 'Last pick reverted successfully!');
        } catch (\Exception $e) {
            \Log::error('revertPick failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 400);
            }

            return back()->withErrors(['error' => 'Failed to revert pick: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete a draft.
     */
    public function destroy(Draft $draft)
    {
        \Log::info('destroy draft called', [
            'draft_id' => $draft->id,
            'draft_name' => $draft->name,
        ]);

        try {
            $leagueId = $draft->league_id;
            $draftName = $draft->name;

            // Delete the draft (cascade will handle picks and rosters)
            $draft->delete();

            \Log::info('Draft deleted successfully', [
                'draft_name' => $draftName,
            ]);

            return redirect()->route('drafts.index')
                ->with('success', "Draft '{$draftName}' deleted successfully!");
        } catch (\Exception $e) {
            \Log::error('destroy draft failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['error' => 'Failed to delete draft: ' . $e->getMessage()]);
        }
    }

    /**
     * Simulate one pick using AI and return the result.
     * Frontend calls this repeatedly to simulate multiple picks.
     */
    public function simulate(Request $request, Draft $draft)
    {
        \Log::info('simulate called', [
            'draft_id' => $draft->id,
            'stop_round' => $request->input('stop_round'),
        ]);

        $validated = $request->validate([
            'stop_round' => 'required|integer|min:1|max:' . $draft->total_rounds,
        ]);

        if (!$draft->isInProgress()) {
            return response()->json([
                'success' => false,
                'error' => 'Draft is not in progress. Please start the draft first.',
            ], 400);
        }

        $stopRound = (int) $validated['stop_round'];

        // Check if we're already past the stop round
        if ($draft->current_round > $stopRound) {
            return response()->json([
                'success' => false,
                'done' => true,
                'message' => 'Simulation complete - reached target round.',
            ]);
        }

        // Get the current pick
        $currentPick = $this->draftSimulator->getCurrentPick($draft);

        if (!$currentPick) {
            return response()->json([
                'success' => false,
                'done' => true,
                'message' => 'No more picks available.',
            ]);
        }

        $team = $currentPick->team;

        try {
            // Get AI recommendations
            $aiService = app(\App\Services\DraftAIService::class);
            $recommendations = $aiService->getRecommendations($draft, $team, 5);

            if (!$recommendations['success'] || empty($recommendations['recommendations'])) {
                $recommendations = $aiService->getFallbackRecommendations($draft, $team, 5);
            }

            if (empty($recommendations['recommendations'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'No recommendations available for this pick.',
                ]);
            }

            // Take the top recommendation
            $topRec = $recommendations['recommendations'][0];
            $player = \App\Models\Player::find($topRec['player_id']);

            if (!$player) {
                return response()->json([
                    'success' => false,
                    'error' => 'Player not found.',
                ]);
            }

            // Determine position to fill
            $positionFilled = $this->draftSimulator->determinePositionToFill($draft, $team, $player);

            // Make the pick
            $pick = $this->draftSimulator->makePick(
                $draft,
                $player,
                $positionFilled,
                ['player_ids' => array_column($recommendations['recommendations'], 'player_id')],
                $topRec['explanation'] ?? 'AI-selected best available'
            );

            // Refresh draft to get updated state
            $draft->refresh();

            // Check if we should continue
            $shouldContinue = $draft->isInProgress() && $draft->current_round <= $stopRound;

            return response()->json([
                'success' => true,
                'done' => !$shouldContinue,
                'pick' => [
                    'overall_pick' => $pick->overall_pick,
                    'round' => $pick->round,
                    'pick_in_round' => $pick->pick_in_round,
                    'team_id' => $team->id,
                    'team' => $team->name,
                    'player_id' => $player->id,
                    'player' => $player->name,
                    'mlb_team' => $player->mlb_team,
                    'roster_position' => $positionFilled,
                    'positions' => $player->positions,
                    'is_pitcher' => $player->is_pitcher,
                    'projected_points' => $topRec['projected_points'] ?? null,
                ],
                'draft_status' => [
                    'current_round' => $draft->current_round,
                    'current_pick' => $draft->current_pick,
                    'is_complete' => $draft->isComplete(),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Simulation pick failed', [
                'draft_id' => $draft->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Pick failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}

