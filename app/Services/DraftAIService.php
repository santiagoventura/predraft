<?php

namespace App\Services;

use App\Models\Draft;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\InjuryDataService;

/**
 * Service for AI-powered draft recommendations using Google Gemini.
 * 
 * Provides:
 * - Top 5 player recommendations for current pick
 * - Detailed explanations for each recommendation
 * - Strategic analysis considering draft dynamics
 */
class DraftAIService
{
    protected DraftSimulator $draftSimulator;
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;
    protected float $temperature;
    protected int $maxTokens;

    protected ScoringCalculator $scoringCalculator;
    protected InjuryDataService $injuryService;

    public function __construct(DraftSimulator $draftSimulator, ScoringCalculator $scoringCalculator, InjuryDataService $injuryService)
    {
        $this->draftSimulator = $draftSimulator;
        $this->scoringCalculator = $scoringCalculator;
        $this->injuryService = $injuryService;
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model', 'gemini-1.5-pro');
        $this->baseUrl = config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
        $this->temperature = config('services.gemini.temperature', 0.7);
        $this->maxTokens = config('services.gemini.max_tokens', 4096);
    }

    /**
     * Get AI recommendations for the current pick.
     */
    public function getRecommendations(Draft $draft, Team $team, int $topN = 5): array
    {
        try {
            // Get draft context
            $context = $this->buildDraftContext($draft, $team);
            
            // Get eligible players
            $eligiblePlayers = $this->draftSimulator->getEligiblePlayers($draft, $team);
            
            if ($eligiblePlayers->isEmpty()) {
                return [
                    'success' => false,
                    'error' => 'No eligible players available',
                ];
            }

            // Build prompt
            $prompt = $this->buildPrompt($context, $eligiblePlayers, $topN);
            
            // Call Gemini API
            $response = $this->callGeminiAPI($prompt);
            
            // Parse response
            $recommendations = $this->parseRecommendations($response, $eligiblePlayers);
            
            return [
                'success' => true,
                'recommendations' => $recommendations,
                'context' => $context,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting AI recommendations', [
                'draft_id' => $draft->id,
                'team_id' => $team->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build draft context for AI analysis.
     */
    protected function buildDraftContext(Draft $draft, Team $team): array
    {
        $league = $draft->league;
        $summary = $this->draftSimulator->getDraftSummary($draft);
        $teamNeeds = $this->draftSimulator->getTeamNeeds($draft, $team);
        $teamRoster = $this->draftSimulator->getTeamRoster($draft, $team);

        // Get scoring categories to help AI understand what stats matter
        $batterCategories = $league->batterScoringCategories->map(fn($c) => [
            'stat' => $c->stat_code,
            'points_per' => $c->points_per_unit,
        ])->toArray();

        $pitcherCategories = $league->pitcherScoringCategories->map(fn($c) => [
            'stat' => $c->stat_code,
            'points_per' => $c->points_per_unit,
        ])->toArray();

        return [
            'league_id' => $league->id,
            'league' => [
                'num_teams' => $league->num_teams,
                'scoring_format' => $league->scoring_format,
                'batter_scoring' => $batterCategories,
                'pitcher_scoring' => $pitcherCategories,
                'positions' => $league->positions->map(fn($p) => [
                    'position' => $p->position_code,
                    'slots' => $p->slot_count,
                ])->toArray(),
            ],
            'draft' => [
                'current_round' => $draft->current_round,
                'total_rounds' => $draft->total_rounds,
                'completed_picks' => $summary['completed_picks'],
                'remaining_picks' => $summary['remaining_picks'],
                'pitchers_picked' => $summary['pitchers_picked'],
                'hitters_picked' => $summary['hitters_picked'],
                'pitcher_percentage' => $summary['pitcher_percentage'],
            ],
            'team' => [
                'name' => $team->name,
                'draft_slot' => $team->draft_slot,
                'roster_count' => $teamRoster->count(),
                'needs' => $teamNeeds,
                'roster' => $teamRoster->map(fn($r) => [
                    'player' => $r->player->name,
                    'position' => $r->roster_position,
                ])->toArray(),
            ],
        ];
    }

    /**
     * Build the prompt for Gemini.
     */
    protected function buildPrompt(array $context, $eligiblePlayers, int $topN): string
    {
        $league = \App\Models\League::find($context['league_id']);

        // Fetch latest injury data (cached for 6 hours)
        $this->injuryService->fetchInjuryData();

        // Get league-specific projected points for each player
        // Process ALL eligible players first, then sort and take top 150
        $playersData = $eligiblePlayers->map(function ($player) use ($league) {
            $ranking = $player->getBestRanking();
            $projection = $player->getLatestProjection();

            // Get league-specific score
            $leagueScore = \App\Models\PlayerScore::where('player_id', $player->id)
                ->where('league_id', $league->id)
                ->where('season', 2026)
                ->first();

            $projectedPoints = $leagueScore?->total_points ?? null;
            $categoryBreakdown = $leagueScore?->category_breakdown ?? null;

            // If no league-specific score, estimate based on ranking and player type
            if (!$projectedPoints && $ranking) {
                // Rough estimation: top players ~500pts, decreases from there
                // Pitchers typically score 60-70% of what batters score
                $basePoints = max(100, 600 - ($ranking->overall_rank * 3));
                $projectedPoints = $player->is_pitcher ? $basePoints * 0.65 : $basePoints;
            }

            // Get real injury status
            $injuryStatus = $this->injuryService->getPlayerInjuryStatus($player->name);

            return [
                'id' => $player->id,
                'name' => $player->name,
                'team' => $player->mlb_team,
                'positions' => $player->positions,
                'primary_position' => $player->primary_position,
                'is_pitcher' => $player->is_pitcher,
                'rank' => $ranking?->overall_rank,
                'adp' => $ranking?->adp,
                'projected_points' => $projectedPoints ? round($projectedPoints, 1) : null,
                'category_breakdown' => $categoryBreakdown,
                'projection' => $this->formatProjection($player, $projection),
                'injury_status' => $injuryStatus, // Real injury data
            ];
        });

        // Sort by projected points and take top 150 to send to AI
        $playersData = $playersData->sortByDesc('projected_points')->take(150)->values();

        // Separate batters and pitchers for better analysis
        $batters = $playersData->where('is_pitcher', false)->sortByDesc('projected_points')->values();
        $pitchers = $playersData->where('is_pitcher', true)->sortByDesc('projected_points')->values();

        // Calculate draft position and picks until next turn
        $numTeams = $context['league']['num_teams'];
        $currentRound = $context['draft']['current_round'];
        $draftSlot = $context['team']['draft_slot'];
        $currentOverallPick = $context['draft']['completed_picks'] + 1;

        // Snake draft: calculate picks until next turn
        // In odd rounds: pick order is 1,2,3...n
        // In even rounds: pick order is n,n-1,...1
        if ($currentRound % 2 == 1) {
            // Odd round - picks left in this round + picks in next round until our turn
            $picksLeftInRound = $numTeams - $draftSlot;
            $picksInNextRound = $draftSlot - 1; // In even round, we pick at position (numTeams - draftSlot + 1)
            $picksUntilNextTurn = $picksLeftInRound + $picksInNextRound + 1;
        } else {
            // Even round - we pick at position (numTeams - draftSlot + 1)
            $ourPositionInRound = $numTeams - $draftSlot + 1;
            $picksLeftInRound = $numTeams - $ourPositionInRound;
            $picksInNextRound = $draftSlot - 1;
            $picksUntilNextTurn = $picksLeftInRound + $picksInNextRound + 1;
        }

        // Build positional scarcity analysis with availability predictions
        $positionalScarcity = $this->buildPositionalScarcityAnalysis(
            $playersData,
            $context['team']['needs'],
            $picksUntilNextTurn,
            $currentOverallPick
        );

        $battersJson = $batters->take(80)->toJson();
        $pitchersJson = $pitchers->take(70)->toJson();
        $contextJson = json_encode($context, JSON_PRETTY_PRINT);
        $scarcityJson = json_encode($positionalScarcity, JSON_PRETTY_PRINT);

        // Calculate pitcher roster requirements
        $pitcherSlots = collect($context['league']['positions'])
            ->where('position', 'P')
            ->sum('slots');

        // Get the best available batter and pitcher separately
        $bestBatter = $batters->first();
        $bestPitcher = $pitchers->first();
        $bestBatterName = $bestBatter['name'] ?? 'N/A';
        $bestBatterPoints = $bestBatter['projected_points'] ?? 0;
        $bestPitcherName = $bestPitcher['name'] ?? 'N/A';
        $bestPitcherPoints = $bestPitcher['projected_points'] ?? 0;

        // Next pick info for prompt
        $nextPickNumber = $currentOverallPick + $picksUntilNextTurn;

        return <<<PROMPT
You are an expert fantasy baseball draft advisor. Your goal is to help build a BALANCED, WINNING team.

DRAFT CONTEXT:
{$contextJson}

=== ⚠️ DRAFT POSITION AWARENESS (CRITICAL!) ===
Current pick: #{$currentOverallPick}
Your NEXT pick will be: #{$nextPickNumber} ({$picksUntilNextTurn} picks away)

Players with ADP lower than {$nextPickNumber} will likely be GONE by your next turn!
Use this to decide: "Can I wait, or must I draft this player NOW?"

=== POSITIONAL SCARCITY ANALYSIS ===
{$scarcityJson}

Understanding scarcity data:
- "tier_drop_points": Gap between #1 and #2 at this position (higher = more urgent)
- "availability_prediction": Will the #2 player be available at your next pick?
- "slots_needed": How many roster spots team still needs at this position
  ⚠️ CRITICAL: If "slots_needed" = 0, the position is FILLED. Do NOT use scarcity for that position!
  Only use scarcity analysis for positions where "slots_needed" > 0
- ⚠️ = Player likely GONE by next pick
- ⚡ = Player at risk of being taken
- ✓ = Safe to wait

=== TWO SEPARATE RANKINGS ===
BATTERS are ranked against OTHER BATTERS (best: {$bestBatterName} with {$bestBatterPoints} pts)
PITCHERS are ranked against OTHER PITCHERS (best: {$bestPitcherName} with {$bestPitcherPoints} pts)

⚠️ CRITICAL: DO NOT compare raw points between batters and pitchers - they are on DIFFERENT SCALES!
⚠️ CRITICAL: Pitchers fill a SPECIFIC POSITION (P) and are JUST AS IMPORTANT as batters
⚠️ CRITICAL: You MUST include pitchers in recommendations if team needs pitchers (P slots_needed > 0)

AVAILABLE BATTERS (sorted by projected points):
{$battersJson}

AVAILABLE PITCHERS (sorted by projected points):
{$pitchersJson}

=== DRAFT STRATEGY RULES ===

1. **POSITIONAL PRIORITY - CRITICAL RULE**
   - ⚠️ SPECIFIC POSITIONS (C, 1B, 2B, 3B, SS, OF, P) are HIGHER PRIORITY than UTIL slots
   - ALWAYS fill specific positions BEFORE filling UTIL slots
   - UTIL is a FALLBACK slot for when all specific positions are filled
   - Example: If you need 2B (slots_needed: 1) and UTIL (slots_needed: 3), prioritize the 2B player even if a different player has slightly higher points

2. **FILLED POSITIONS - CRITICAL RULE**
   - ⚠️ If a position shows "slots_needed": 0, that position is ALREADY FILLED
   - DO NOT prioritize positional scarcity for filled positions
   - Example: If SS shows "slots_needed": 0, a SS can only fill UTIL - compare them to OTHER UTIL-eligible players by POINTS, not by SS scarcity
   - Only consider positional scarcity when "slots_needed" > 0

3. **AVAILABILITY FIRST - WILL THEY BE THERE NEXT ROUND?**
   - If a player's ADP < {$nextPickNumber}, they will likely be GONE by your next pick
   - If the #2 player at a position won't be available next round, and the gap to #3 is huge → DRAFT #1 NOW
   - Example: "Best 3B has 450 pts, #2 3B has ADP 12 (won't be there at pick {$nextPickNumber}), #3 3B has only 380 pts → MUST draft the 3B now!"
   - ⚠️ BUT: Only apply this if the position still needs to be filled (slots_needed > 0)

4. **POSITIONAL SCARCITY IS KING (for unfilled SPECIFIC positions)**
   - tier_drop > 50 points + team needs position (slots_needed > 0) → HIGH PRIORITY
   - A 3B with 400 pts (next 3B: 340 pts, slots_needed: 1) > SS with 420 pts (next SS: 410 pts, slots_needed: 1)
   - ⚠️ IGNORE scarcity if slots_needed = 0
   - ⚠️ Scarcity only matters for SPECIFIC positions, NOT for UTIL

5. **BALANCE BATTERS AND PITCHERS - CRITICAL**
   - ⚠️ League requires {$pitcherSlots} pitcher slots per team - this is A LOT of pitchers!
   - ⚠️ Pitchers are JUST AS IMPORTANT as batters - do NOT ignore them
   - Elite pitchers (top 5-10) should go in rounds 1-4
   - Compare pitchers to OTHER PITCHERS, not to batters
   - If best pitcher is 50+ points ahead of #2 pitcher → strongly consider
   - ⚠️ ALWAYS include at least 1-2 pitchers in your recommendations if team needs pitchers (P slots_needed > 0)
   - Pitchers fill a SPECIFIC POSITION (P), so they have HIGHER PRIORITY than UTIL-only players

6. **FILL EVERY SPECIFIC POSITION FIRST**
   - Empty roster slot = 0 points
   - If team needs catchers and only 3 good ones left → draft one NOW
   - Fill C, 1B, 2B, 3B, SS, OF, P positions BEFORE worrying about UTIL

7. **UTIL SLOTS - LOWEST PRIORITY**
   - Only focus on UTIL when ALL specific positions are filled
   - For UTIL slots: Compare players by PROJECTED POINTS only, not positional scarcity
   - Example: If only UTIL slots remain, draft the highest projected points player available

=== INJURY GUIDELINES ===
- Check "injury_status" field for each player
- If "Check MLB.com injury report": verify at https://www.mlb.com/injury-report
- Consider injury history (Tommy John, recurring issues)

=== RESPONSE FORMAT ===
Respond with ONLY valid JSON:
{
  "recommendations": [
    {
      "player_id": 123,
      "player_name": "Player Name",
      "position": "3B",
      "projected_points": 450.5,
      "rank": 1,
      "scarcity_score": "CRITICAL/HIGH/MEDIUM/LOW",
      "availability_note": "Must draft now - next best 3B (ADP 15) won't be there at pick 22",
      "injury_status": "Copy exact injury_status from player data",
      "pros": ["Strength 1", "Strength 2"],
      "cons": ["Weakness 1"],
      "position_context": "Best 3B available. Next: Player X (380 pts, ADP 18 - will be available). Gap: 70 pts.",
      "explanation": "2-3 sentences: Why this pick NOW? Include availability analysis if relevant."
    }
  ]
}

=== FINAL INSTRUCTIONS ===
- Provide exactly {$topN} recommendations
- ⚠️ CRITICAL TOP BATTER RULE: The #1 batter by projected points MUST ALWAYS be included in recommendations if:
  - They can fill ANY roster slot (specific position OR UTIL)
  - They are not already drafted
  - This is NON-NEGOTIABLE - the best batter available should always be an option
- ⚠️ CRITICAL PITCHER REQUIREMENT: If team needs pitchers (P slots_needed > 0), you MUST include AT LEAST 1-2 PITCHERS in your top {$topN} recommendations
  - Pitchers are ESSENTIAL and often overlooked
  - Compare pitchers to OTHER PITCHERS, not to batters
  - If team needs many pitchers (P slots_needed >= 8), include 2 pitchers in recommendations
  - Elite pitchers (top 10) should be strongly considered even in early rounds
- Order by DRAFT VALUE with this priority:
  1. SPECIFIC POSITIONS with slots_needed > 0 (use scarcity + availability + points)
  2. UTIL slots (use POINTS ONLY, ignore positional scarcity)
- ⚠️ CRITICAL: SPECIFIC POSITIONS (C, 1B, 2B, 3B, SS, OF, P) are ALWAYS higher priority than UTIL
- ⚠️ CRITICAL: Only use positional scarcity when "slots_needed" > 0 for that SPECIFIC position
- If a position is filled (slots_needed = 0), players at that position can only fill UTIL - rank them by POINTS vs other UTIL options
- The #1 pick should be the best VALUE considering what will be available at pick #{$nextPickNumber}
- In explanation, ALWAYS mention if the next-best player at this position will/won't be available next round
- Example (top batter): "Freddie Freeman (1011 pts) is the #1 batter available. Even though 1B is filled, he MUST be recommended as he can fill UTIL. Never skip the top batter!"
- Example (specific position needed): "Gunnar Henderson is the clear #1 SS with 520 pts. Team needs SS (slots_needed: 1). Next SS is Willy Adames (ADP 25) who should still be available at your pick #{$nextPickNumber}, but the 45-point gap makes Henderson the better value now."
- Example (position filled, only UTIL available): "SS is already filled (slots_needed: 0). While Gunnar Henderson has high SS scarcity, he would only fill UTIL. Bobby Witt Jr. (OF, 540 pts, slots_needed: 1) is better because OF is a SPECIFIC position that needs to be filled."
- Example (pitcher recommendation): "Team needs 10 pitchers. Tarik Skubal (SP, 380 pts) is the #1 pitcher available. Next best pitcher is Corbin Burnes (ADP 15) who won't be available at pick #{$nextPickNumber}. Must draft elite pitcher now."
PROMPT;
    }

    /**
     * Build positional scarcity analysis with tier drops and availability predictions.
     */
    protected function buildPositionalScarcityAnalysis(
        $playersData,
        array $teamNeeds,
        int $picksUntilNextTurn = 0,
        int $currentOverallPick = 0
    ): array {
        $positions = ['C', '1B', '2B', '3B', 'SS', 'OF', 'DH', 'SP', 'RP', 'P'];
        $scarcity = [];

        foreach ($positions as $position) {
            // Get players at this position, sorted by points
            $positionPlayers = $playersData->filter(function ($player) use ($position) {
                $playerPositions = explode(',', $player['positions'] ?? '');
                $playerPositions = array_map('trim', $playerPositions);

                // For pitchers, check SP, RP, or P
                if (in_array($position, ['SP', 'RP', 'P'])) {
                    return $player['is_pitcher'] &&
                           (in_array($position, $playerPositions) || in_array('P', $playerPositions));
                }

                return in_array($position, $playerPositions);
            })->sortByDesc('projected_points')->values();

            if ($positionPlayers->isEmpty()) {
                continue;
            }

            $top5 = $positionPlayers->take(5);
            $best = $top5->first();
            $second = $top5->get(1);
            $third = $top5->get(2);

            // Calculate tier drop (gap between #1 and #2)
            $tierDrop = 0;
            if ($best && $second) {
                $tierDrop = round(($best['projected_points'] ?? 0) - ($second['projected_points'] ?? 0), 1);
            }

            // Predict availability based on ADP
            $availabilityNote = '';
            if ($picksUntilNextTurn > 0 && $second) {
                $secondAdp = $second['adp'] ?? $second['rank'] ?? 999;
                $nextPickNumber = $currentOverallPick + $picksUntilNextTurn;

                if ($secondAdp < $nextPickNumber) {
                    $availabilityNote = "⚠️ #{$second['name']} (ADP: {$secondAdp}) likely GONE by your next pick (#{$nextPickNumber})";
                } elseif ($secondAdp < $nextPickNumber + 5) {
                    $availabilityNote = "⚡ #{$second['name']} at risk - ADP {$secondAdp} close to next pick #{$nextPickNumber}";
                } else {
                    $availabilityNote = "✓ Options likely available next round";
                }
            }

            // Determine scarcity level
            $scarcityLevel = 'LOW';
            if ($tierDrop >= 50) {
                $scarcityLevel = 'CRITICAL';
            } elseif ($tierDrop >= 30) {
                $scarcityLevel = 'HIGH';
            } elseif ($tierDrop >= 15) {
                $scarcityLevel = 'MEDIUM';
            }

            // Slots needed for this position
            $slotsNeeded = $teamNeeds[$position] ?? 0;

            $scarcity[$position] = [
                'scarcity_level' => $scarcityLevel,
                'slots_needed' => $slotsNeeded,
                'players_available' => $positionPlayers->count(),
                'tier_drop_points' => $tierDrop,
                'availability_prediction' => $availabilityNote,
                'top_3' => $top5->take(3)->map(fn($p) => [
                    'name' => $p['name'],
                    'points' => $p['projected_points'],
                    'adp' => $p['adp'] ?? $p['rank'] ?? 'N/A',
                ])->values()->toArray(),
            ];
        }

        // Sort by scarcity level and tier drop
        uasort($scarcity, function ($a, $b) {
            $levelOrder = ['CRITICAL' => 0, 'HIGH' => 1, 'MEDIUM' => 2, 'LOW' => 3];
            $aLevel = $levelOrder[$a['scarcity_level']] ?? 3;
            $bLevel = $levelOrder[$b['scarcity_level']] ?? 3;

            if ($aLevel !== $bLevel) {
                return $aLevel - $bLevel;
            }
            return ($b['tier_drop_points'] ?? 0) - ($a['tier_drop_points'] ?? 0);
        });

        return $scarcity;
    }

    /**
     * Format player projection for the prompt.
     */
    protected function formatProjection(Player $player, $projection): ?array
    {
        if (!$projection) {
            return null;
        }

        if ($player->is_pitcher) {
            return [
                'IP' => $projection->ip,
                'W' => $projection->w,
                'K' => $projection->k,
                'ERA' => $projection->era,
                'WHIP' => $projection->whip,
                'SV' => $projection->sv,
            ];
        } else {
            return [
                'HR' => $projection->hr,
                'R' => $projection->r,
                'RBI' => $projection->rbi,
                'SB' => $projection->sb,
                'AVG' => $projection->avg,
                'OPS' => $projection->ops,
            ];
        }
    }

    /**
     * Call the Gemini API.
     */
    protected function callGeminiAPI(string $prompt): string
    {
        $url = "{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}";

        $response = Http::timeout(30)->post($url, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $this->temperature,
                'maxOutputTokens' => $this->maxTokens,
            ],
        ]);

        if (!$response->successful()) {
            throw new \Exception('Gemini API request failed: ' . $response->body());
        }

        $data = $response->json();

        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            throw new \Exception('Unexpected Gemini API response format');
        }

        return $data['candidates'][0]['content']['parts'][0]['text'];
    }

    /**
     * Parse AI recommendations from response.
     * IMPORTANT: We preserve the AI's ordering because it considers strategic factors
     * like pitcher timing, positional scarcity, and team needs - not just raw points.
     */
    protected function parseRecommendations(string $response, $eligiblePlayers): array
    {
        // Extract JSON from response (in case there's extra text)
        $jsonMatch = preg_match('/\{[\s\S]*\}/', $response, $matches);

        if (!$jsonMatch) {
            throw new \Exception('Could not extract JSON from AI response');
        }

        $json = json_decode($matches[0], true);

        if (!$json || !isset($json['recommendations'])) {
            throw new \Exception('Invalid JSON format in AI response');
        }

        $recommendations = [];
        foreach ($json['recommendations'] as $rec) {
            if (!isset($rec['player_id']) || !isset($rec['explanation'])) {
                continue;
            }

            $player = $eligiblePlayers->firstWhere('id', $rec['player_id']);

            if ($player) {
                // Get real injury status from injury service
                $realInjuryStatus = $this->injuryService->getPlayerInjuryStatus($player->name);

                // Get the actual projected points from database to ensure accuracy
                $leagueScore = \App\Models\PlayerScore::where('player_id', $player->id)
                    ->where('season', 2026)
                    ->first();
                $actualProjectedPoints = $leagueScore?->total_points ?? $rec['projected_points'] ?? 0;

                // Get ADP data
                $adpRanking = \App\Models\PlayerRanking::where('player_id', $player->id)
                    ->where('source', 'fantasypros_adp')
                    ->where('season', 2025)
                    ->first();

                $recommendations[] = [
                    'player_id' => $player->id,
                    'player_name' => $player->name,
                    'player_team' => $player->mlb_team,
                    'positions' => $player->positions,
                    'position' => $rec['position'] ?? $player->primary_position,
                    'is_pitcher' => $player->is_pitcher,
                    'rank' => $rec['rank'] ?? null,
                    'adp' => $adpRanking?->adp ? (float) $adpRanking->adp : null,
                    'projected_points' => round($actualProjectedPoints, 1),
                    'injury_status' => $realInjuryStatus, // Use real injury data
                    'pros' => $rec['pros'] ?? [],
                    'cons' => $rec['cons'] ?? [],
                    'position_context' => $rec['position_context'] ?? '',
                    'explanation' => $rec['explanation'],
                ];
            }
        }

        // DO NOT sort by projected_points - the AI's ordering considers strategic factors:
        // - Pitcher timing (elite pitchers should go in early rounds)
        // - Positional scarcity
        // - Team roster needs
        // Sorting by raw points would always favor batters over pitchers, which is wrong.

        return $recommendations;
    }

    /**
     * Get a smart fallback recommendation that considers team needs.
     * Used when AI service is unavailable.
     * Balances best available players with positional needs (especially pitchers).
     */
    public function getFallbackRecommendations(Draft $draft, Team $team, int $topN = 5): array
    {
        $eligiblePlayers = $this->draftSimulator->getEligiblePlayers($draft, $team);
        $league = $draft->league;
        $teamNeeds = $this->draftSimulator->getTeamNeeds($draft, $team);

        // Check if team needs pitchers
        $pitcherSlotsNeeded = 0;
        foreach ($teamNeeds as $position => $needed) {
            if (in_array($position, ['SP', 'RP', 'P'])) {
                $pitcherSlotsNeeded += $needed;
            }
        }

        // Get players with their projected points
        $playersWithPoints = $eligiblePlayers->map(function ($player) use ($league) {
            // Get league-specific score
            $leagueScore = \App\Models\PlayerScore::where('player_id', $player->id)
                ->where('league_id', $league->id)
                ->where('season', 2026)
                ->first();

            $projectedPoints = $leagueScore?->total_points ?? 0;

            // If no league-specific score, fall back to ranking
            if (!$projectedPoints) {
                $ranking = $player->getBestRanking();
                if ($ranking) {
                    // Estimate points from ranking
                    $basePoints = max(100, 600 - ($ranking->overall_rank * 3));
                    $projectedPoints = $player->is_pitcher ? $basePoints * 0.65 : $basePoints;
                }
            }

            return [
                'player' => $player,
                'projected_points' => $projectedPoints,
            ];
        });

        // Split into batters and pitchers
        $batters = $playersWithPoints->filter(fn($p) => !$p['player']->is_pitcher)->sortByDesc('projected_points');
        $pitchers = $playersWithPoints->filter(fn($p) => $p['player']->is_pitcher)->sortByDesc('projected_points');

        // Build recommendations with a mix of batters and pitchers
        $recommendations = collect();

        // If we're in early rounds (1-4) and need pitchers, include top pitchers
        $currentRound = $draft->current_round;
        if ($currentRound <= 4 && $pitcherSlotsNeeded > 0 && $pitchers->isNotEmpty()) {
            // Add top pitcher as first or second recommendation
            $topPitcher = $pitchers->first();
            $topBatter = $batters->first();

            // In early rounds, elite pitchers are valuable - include them
            if ($topPitcher) {
                // Check if this pitcher is elite (top 50 ranking or high points relative to other pitchers)
                $pitcherRanking = $topPitcher['player']->getBestRanking();
                $isElitePitcher = $pitcherRanking && $pitcherRanking->overall_rank <= 50;

                if ($isElitePitcher) {
                    $recommendations->push($topPitcher);
                }
            }
        }

        // Add top batters
        foreach ($batters->take($topN) as $item) {
            if ($recommendations->count() >= $topN) break;
            $recommendations->push($item);
        }

        // Ensure we have at least 1-2 pitchers in recommendations if team needs them
        if ($pitcherSlotsNeeded > 0 && $recommendations->filter(fn($r) => $r['player']->is_pitcher)->isEmpty()) {
            // Add top pitcher
            $topPitcher = $pitchers->first();
            if ($topPitcher && $recommendations->count() >= $topN) {
                // Replace last recommendation with pitcher
                $recommendations->pop();
            }
            if ($topPitcher) {
                $recommendations->push($topPitcher);
            }
        }

        // Format recommendations
        $formattedRecs = $recommendations
            ->take($topN)
            ->values()
            ->map(function ($item, $index) {
                $player = $item['player'];
                $type = $player->is_pitcher ? 'pitcher' : 'batter';

                // Get ADP data
                $adpRanking = \App\Models\PlayerRanking::where('player_id', $player->id)
                    ->where('source', 'fantasypros_adp')
                    ->where('season', 2025)
                    ->first();

                return [
                    'player_id' => $player->id,
                    'player_name' => $player->name,
                    'player_team' => $player->mlb_team,
                    'positions' => $player->positions,
                    'is_pitcher' => $player->is_pitcher,
                    'rank' => $index + 1,
                    'adp' => $adpRanking?->adp ? (float) $adpRanking->adp : null,
                    'projected_points' => round($item['projected_points'], 1),
                    'explanation' => "Best available {$type} by projected points.",
                ];
            })
            ->toArray();

        return [
            'success' => true,
            'recommendations' => $formattedRecs,
            'fallback' => true,
        ];
    }
}
