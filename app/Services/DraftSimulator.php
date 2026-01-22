<?php

namespace App\Services;

use App\Models\Draft;
use App\Models\DraftPick;
use App\Models\League;
use App\Models\Player;
use App\Models\Team;
use App\Models\TeamRoster;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing draft simulation logic.
 * 
 * Handles:
 * - Draft initialization
 * - Snake draft order calculation
 * - Pick advancement
 * - Roster tracking
 * - Available player filtering
 */
class DraftSimulator
{
    /**
     * Initialize a new draft for a league.
     */
    public function initializeDraft(League $league, string $draftName = null): Draft
    {
        DB::beginTransaction();
        try {
            // Calculate total rounds based on roster positions
            $totalRounds = $league->positions()->sum('slot_count');

            // Create the draft
            $draft = Draft::create([
                'league_id' => $league->id,
                'name' => $draftName ?? "Draft - " . now()->format('Y-m-d H:i'),
                'status' => 'setup',
                'draft_type' => 'snake',
                'current_round' => 1,
                'current_pick' => 1,
                'total_rounds' => $totalRounds,
            ]);

            // Generate all draft picks (empty slots)
            $this->generateDraftPicks($draft, $league);

            DB::commit();
            return $draft;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error initializing draft', [
                'league_id' => $league->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate all draft pick slots for a snake draft.
     */
    protected function generateDraftPicks(Draft $draft, League $league): void
    {
        $teams = $league->teams()->orderBy('draft_slot')->get();
        $numTeams = $teams->count();
        $totalRounds = $draft->total_rounds;
        $overallPick = 1;

        for ($round = 1; $round <= $totalRounds; $round++) {
            // Snake draft: reverse order on even rounds
            $roundTeams = ($round % 2 === 0) ? $teams->reverse() : $teams;

            $pickInRound = 1;
            foreach ($roundTeams as $team) {
                DraftPick::create([
                    'draft_id' => $draft->id,
                    'round' => $round,
                    'pick_in_round' => $pickInRound,
                    'overall_pick' => $overallPick,
                    'team_id' => $team->id,
                ]);

                $pickInRound++;
                $overallPick++;
            }
        }
    }

    /**
     * Start the draft.
     */
    public function startDraft(Draft $draft): Draft
    {
        $draft->update([
            'status' => 'in_progress',
            'started_at' => now(),
            'current_team_id' => $this->getCurrentPick($draft)->team_id,
        ]);

        return $draft->fresh();
    }

    /**
     * Get the current pick.
     */
    public function getCurrentPick(Draft $draft): ?DraftPick
    {
        return $draft->picks()
            ->where('round', $draft->current_round)
            ->where('pick_in_round', $draft->current_pick)
            ->first();
    }

    /**
     * Make a pick for the current team.
     */
    public function makePick(
        Draft $draft,
        Player $player,
        string $positionFilled,
        ?array $recommendations = null,
        ?string $aiExplanation = null
    ): DraftPick {
        DB::beginTransaction();
        try {
            $currentPick = $this->getCurrentPick($draft);

            if (!$currentPick) {
                throw new \Exception('No current pick found');
            }

            if ($currentPick->isPicked()) {
                throw new \Exception('This pick has already been made');
            }

            // Check if player has already been drafted
            $alreadyDrafted = $draft->picks()
                ->where('player_id', $player->id)
                ->whereNotNull('player_id')
                ->first();

            if ($alreadyDrafted) {
                $team = $alreadyDrafted->team;
                throw new \Exception("Player {$player->name} has already been drafted by {$team->name} (Pick #{$alreadyDrafted->overall_pick})");
            }

            // Update the pick
            $currentPick->update([
                'player_id' => $player->id,
                'position_filled' => $positionFilled,
                'recommendations' => $recommendations,
                'ai_explanation' => $aiExplanation,
                'picked_at' => now(),
            ]);

            // Add to team roster
            TeamRoster::create([
                'draft_id' => $draft->id,
                'team_id' => $currentPick->team_id,
                'player_id' => $player->id,
                'roster_position' => $positionFilled,
                'draft_pick_id' => $currentPick->id,
            ]);

            // Advance to next pick
            $this->advanceToNextPick($draft);

            DB::commit();
            return $currentPick->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error making pick', [
                'draft_id' => $draft->id,
                'player_id' => $player->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Advance to the next pick.
     */
    protected function advanceToNextPick(Draft $draft): void
    {
        $league = $draft->league;
        $numTeams = $league->teams()->count();

        // Check if round is complete
        if ($draft->current_pick >= $numTeams) {
            // Move to next round
            $draft->current_round++;
            $draft->current_pick = 1;
        } else {
            // Next pick in same round
            $draft->current_pick++;
        }

        // Check if draft is complete
        if ($draft->current_round > $draft->total_rounds) {
            $draft->status = 'completed';
            $draft->completed_at = now();
            $draft->current_team_id = null;
        } else {
            // Update current team
            $nextPick = $this->getCurrentPick($draft);
            $draft->current_team_id = $nextPick ? $nextPick->team_id : null;
        }

        $draft->save();
    }

    /**
     * Revert the last pick made in the draft.
     */
    public function revertLastPick(Draft $draft): ?DraftPick
    {
        DB::beginTransaction();
        try {
            // Find the last completed pick
            $lastPick = $draft->picks()
                ->whereNotNull('player_id')
                ->orderBy('overall_pick', 'desc')
                ->first();

            if (!$lastPick) {
                throw new \Exception('No picks to revert');
            }

            // Remove from team roster
            TeamRoster::where('draft_pick_id', $lastPick->id)->delete();

            // Clear the pick data
            $lastPick->update([
                'player_id' => null,
                'position_filled' => null,
                'recommendations' => null,
                'ai_explanation' => null,
                'picked_at' => null,
            ]);

            // Move draft back to this pick
            $draft->update([
                'current_round' => $lastPick->round,
                'current_pick' => $lastPick->pick_in_round,
                'current_team_id' => $lastPick->team_id,
                'status' => 'in_progress', // In case draft was completed
                'completed_at' => null,
            ]);

            DB::commit();
            Log::info('Pick reverted successfully', [
                'draft_id' => $draft->id,
                'pick_id' => $lastPick->id,
                'overall_pick' => $lastPick->overall_pick,
            ]);

            return $lastPick->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error reverting pick', [
                'draft_id' => $draft->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get available players (not yet drafted).
     */
    public function getAvailablePlayers(Draft $draft, ?bool $isPitcher = null): \Illuminate\Database\Eloquent\Collection
    {
        $draftedPlayerIds = $draft->picks()
            ->whereNotNull('player_id')
            ->pluck('player_id')
            ->toArray();

        $query = Player::whereNotIn('id', $draftedPlayerIds);

        if ($isPitcher !== null) {
            $query->where('is_pitcher', $isPitcher);
        }

        return $query->get();
    }

    /**
     * Get team's current roster for a draft.
     */
    public function getTeamRoster(Draft $draft, Team $team): \Illuminate\Database\Eloquent\Collection
    {
        return TeamRoster::where('draft_id', $draft->id)
            ->where('team_id', $team->id)
            ->with('player')
            ->get();
    }

    /**
     * Get team's remaining positional needs.
     */
    public function getTeamNeeds(Draft $draft, Team $team): array
    {
        $league = $draft->league;
        $positions = $league->positions;

        // Get current roster
        $roster = $this->getTeamRoster($draft, $team);

        // Count filled positions
        $filledPositions = $roster->groupBy('roster_position')
            ->map(fn($group) => $group->count())
            ->toArray();

        // Calculate needs
        $needs = [];
        foreach ($positions as $position) {
            $filled = $filledPositions[$position->position_code] ?? 0;
            $needed = $position->slot_count - $filled;

            if ($needed > 0) {
                $needs[$position->position_code] = $needed;
            }
        }

        return $needs;
    }

    /**
     * Automatically determine the best position to fill for a player.
     * Uses the same logic as the Python draft simulator.
     */
    public function determinePositionToFill(Draft $draft, Team $team, Player $player): string
    {
        $league = $draft->league;
        $roster = $this->getTeamRoster($draft, $team);

        // Get all filled roster positions
        $filledPositions = $roster->pluck('roster_position')->toArray();

        // Parse player's eligible positions
        // Handle both comma-separated (e.g., "1B,OF") and slash-separated (e.g., "DH/OF")
        $positionsString = str_replace('/', ',', $player->positions); // Convert slashes to commas
        $playerPositions = array_map('trim', explode(',', $positionsString));

        // Treat DH as UTIL (DH is just a UTIL slot in fantasy baseball)
        $playerPositions = array_map(function($pos) {
            return $pos === 'DH' ? 'UTIL' : $pos;
        }, $playerPositions);

        // STEP 1: Handle UTIL-only players (can only go to UTIL)
        // Only if they have no other positions besides UTIL/DH
        if (count($playerPositions) === 1 && $playerPositions[0] === 'UTIL') {
            for ($i = 1; $i <= 3; $i++) {
                $positionSlot = 'UTIL' . $i;
                if (!in_array($positionSlot, $filledPositions)) {
                    return $positionSlot;
                }
            }
            throw new \Exception('No UTIL slots available for UTIL-only player');
        }

        // STEP 2: Try standard positions first (C, 1B, 2B, SS, 3B)
        // This prioritizes specific positions over OF and UTIL
        foreach ($playerPositions as $pos) {
            // Skip OF, UTIL, SP, P positions for now (lower priority)
            if (in_array($pos, ['OF', 'UTIL', 'SP', 'P'])) {
                continue;
            }

            // Check if this single-slot position is available
            if (!in_array($pos, $filledPositions)) {
                return $pos;
            }
        }

        // STEP 3: Try OF positions if player can play OF
        // OF is higher priority than UTIL
        if (in_array('OF', $playerPositions)) {
            for ($i = 1; $i <= 3; $i++) {
                $positionSlot = 'OF' . $i;
                if (!in_array($positionSlot, $filledPositions)) {
                    return $positionSlot;
                }
            }
        }

        // STEP 4: Try P positions if player is a pitcher
        if ($player->is_pitcher || in_array('P', $playerPositions) || in_array('SP', $playerPositions)) {
            for ($i = 1; $i <= 11; $i++) {
                $positionSlot = 'P' . $i;
                if (!in_array($positionSlot, $filledPositions)) {
                    return $positionSlot;
                }
            }
        }

        // STEP 5: Last resort - assign to UTIL if player is a batter
        // This includes players who only have DH (converted to UTIL above)
        if (!$player->is_pitcher && !in_array('P', $playerPositions) && !in_array('SP', $playerPositions)) {
            for ($i = 1; $i <= 3; $i++) {
                $positionSlot = 'UTIL' . $i;
                if (!in_array($positionSlot, $filledPositions)) {
                    return $positionSlot;
                }
            }
        }

        throw new \Exception('No available roster positions for this player');
    }

    /**
     * Get eligible players for a team's needs.
     */
    public function getEligiblePlayers(Draft $draft, Team $team): \Illuminate\Database\Eloquent\Collection
    {
        $needs = $this->getTeamNeeds($draft, $team);
        $availablePlayers = $this->getAvailablePlayers($draft);

        // Filter players who can fill at least one needed position
        return $availablePlayers->filter(function ($player) use ($needs) {
            foreach (array_keys($needs) as $position) {
                if ($player->isEligibleFor($position)) {
                    return true;
                }
            }
            return false;
        });
    }

    /**
     * Get draft summary statistics.
     */
    public function getDraftSummary(Draft $draft): array
    {
        $totalPicks = $draft->picks()->count();
        $completedPicks = $draft->picks()->whereNotNull('player_id')->count();
        $pitchersPicked = $draft->picks()
            ->whereNotNull('player_id')
            ->whereHas('player', fn($q) => $q->where('is_pitcher', true))
            ->count();
        $hittersPicked = $completedPicks - $pitchersPicked;

        return [
            'total_picks' => $totalPicks,
            'completed_picks' => $completedPicks,
            'remaining_picks' => $totalPicks - $completedPicks,
            'pitchers_picked' => $pitchersPicked,
            'hitters_picked' => $hittersPicked,
            'pitcher_percentage' => $completedPicks > 0 ? round(($pitchersPicked / $completedPicks) * 100, 1) : 0,
            'hitter_percentage' => $completedPicks > 0 ? round(($hittersPicked / $completedPicks) * 100, 1) : 0,
        ];
    }

    /**
     * Simulate draft rounds using AI recommendations until a specified round.
     *
     * @param Draft $draft The draft to simulate
     * @param int $stopRound The round to stop at (inclusive)
     * @param callable|null $progressCallback Callback for progress updates: function(int $currentPick, int $totalPicks, array $pickData)
     * @return array Summary of simulation results
     */
    public function simulateRounds(Draft $draft, int $stopRound, ?callable $progressCallback = null): array
    {
        $results = [
            'success' => true,
            'picks_made' => [],
            'errors' => [],
            'stopped_at_round' => null,
        ];

        // Validate stop round
        if ($stopRound < 1 || $stopRound > $draft->total_rounds) {
            $results['success'] = false;
            $results['errors'][] = "Invalid stop round. Must be between 1 and {$draft->total_rounds}";
            return $results;
        }

        // Make sure draft is in progress
        if (!$draft->isInProgress()) {
            $results['success'] = false;
            $results['errors'][] = "Draft is not in progress";
            return $results;
        }

        // Get AI service
        $aiService = app(DraftAIService::class);

        // Calculate total picks needed
        $numTeams = $draft->league->teams()->count();
        $startRound = $draft->current_round;
        $startPick = $draft->current_pick;

        // Calculate total picks to simulate
        $picksInCurrentRound = $numTeams - $startPick + 1;
        $fullRoundsToSimulate = $stopRound - $startRound;
        $totalPicksToSimulate = $picksInCurrentRound + ($fullRoundsToSimulate * $numTeams);

        // If starting fresh in a round, adjust calculation
        if ($startPick === 1) {
            $totalPicksToSimulate = ($stopRound - $startRound + 1) * $numTeams;
        }

        $picksMade = 0;

        // Continue until we pass the stop round
        while ($draft->current_round <= $stopRound && $draft->isInProgress()) {
            $currentPick = $this->getCurrentPick($draft);

            if (!$currentPick) {
                $results['errors'][] = "No current pick found at round {$draft->current_round}";
                break;
            }

            $team = $currentPick->team;

            try {
                // Get AI recommendations
                $recommendations = $aiService->getRecommendations($draft, $team, 5);

                if (!$recommendations['success'] || empty($recommendations['recommendations'])) {
                    // Fallback to simple recommendations
                    $recommendations = $aiService->getFallbackRecommendations($draft, $team, 5);
                }

                if (empty($recommendations['recommendations'])) {
                    $results['errors'][] = "No recommendations available for pick #{$currentPick->overall_pick}";
                    break;
                }

                // Take the top recommendation
                $topRec = $recommendations['recommendations'][0];
                $player = Player::find($topRec['player_id']);

                if (!$player) {
                    $results['errors'][] = "Player not found: {$topRec['player_id']}";
                    break;
                }

                // Determine position to fill
                $positionFilled = $this->determinePositionToFill($draft, $team, $player);

                // Make the pick
                $pick = $this->makePick(
                    $draft,
                    $player,
                    $positionFilled,
                    ['player_ids' => array_column($recommendations['recommendations'], 'player_id')],
                    $topRec['explanation'] ?? 'AI-selected best available'
                );

                $pickData = [
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
                ];

                $results['picks_made'][] = $pickData;
                $picksMade++;

                // Call progress callback if provided
                if ($progressCallback) {
                    $progressCallback($picksMade, $totalPicksToSimulate, $pickData);
                }

                // Refresh draft to get updated state
                $draft->refresh();

            } catch (\Exception $e) {
                Log::error('Error during simulation pick', [
                    'draft_id' => $draft->id,
                    'pick' => $currentPick->overall_pick,
                    'error' => $e->getMessage(),
                ]);
                $results['errors'][] = "Error at pick #{$currentPick->overall_pick}: " . $e->getMessage();
                break;
            }
        }

        $results['stopped_at_round'] = $draft->current_round;
        $results['total_picks_made'] = $picksMade;

        return $results;
    }
}
