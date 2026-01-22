<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get the draft
$draft = \App\Models\Draft::where('status', 'in_progress')->latest()->first();

if (!$draft) {
    echo "No draft in progress found.\n";
    exit(1);
}

$team = $draft->currentTeam;

echo "=== DRAFT INFO ===\n";
echo "Draft: {$draft->name}\n";
echo "Round: {$draft->current_round}\n";
echo "Team: {$team->name}\n\n";

// Get team needs
$simulator = app(\App\Services\DraftSimulator::class);
$needs = $simulator->getTeamNeeds($draft, $team);

echo "=== TEAM NEEDS ===\n";
foreach ($needs as $position => $count) {
    echo "{$position}: {$count}\n";
}
echo "\n";

// Get roster
$roster = $simulator->getTeamRoster($draft, $team);
echo "=== CURRENT ROSTER ===\n";
foreach ($roster as $r) {
    echo "{$r->roster_position}: {$r->player->name} ({$r->player->positions})\n";
}
echo "\n";

// Get AI recommendations
echo "=== GETTING AI RECOMMENDATIONS ===\n";
$aiService = app(\App\Services\DraftAIService::class);
$result = $aiService->getRecommendations($draft, $team, 5);

if (!$result['success']) {
    echo "AI recommendations failed: " . ($result['error'] ?? 'Unknown error') . "\n";
    echo "Using fallback recommendations...\n";
    $result = $aiService->getFallbackRecommendations($draft, $team, 5);
}

echo "\n=== TOP 5 RECOMMENDATIONS ===\n";
foreach ($result['recommendations'] as $i => $rec) {
    echo "\n" . ($i + 1) . ". {$rec['player_name']} ({$rec['positions']})\n";
    echo "   Projected Points: {$rec['projected_points']}\n";
    echo "   ADP: " . ($rec['adp'] ?? 'N/A') . "\n";
    if (isset($rec['explanation'])) {
        echo "   Explanation: {$rec['explanation']}\n";
    }
}

// Check for Caleb Durbin and Diaz
echo "\n\n=== CHECKING CALEB DURBIN ===\n";
$durbin = \App\Models\Player::where('name', 'LIKE', '%Durbin%')->first();
if ($durbin) {
    echo "Name: {$durbin->name}\n";
    echo "Positions: {$durbin->positions}\n";

    $projection = $durbin->getLatestProjection('fantasypros', 2025);
    if ($projection) {
        $scoringCalc = app(\App\Services\ScoringCalculator::class);
        $points = $scoringCalc->calculatePlayerScore($durbin, $draft->league, $projection);
        echo "Projected Points: " . round($points, 1) . "\n";
    } else {
        echo "Projected Points: No projection available\n";
    }

    $adpRanking = \App\Models\PlayerRanking::where('player_id', $durbin->id)
        ->where('source', 'fantasypros_adp')
        ->where('season', 2026)
        ->first();
    echo "ADP: " . ($adpRanking ? $adpRanking->adp : 'N/A') . "\n";

    // Check if drafted
    $drafted = \App\Models\TeamRoster::where('draft_id', $draft->id)
        ->where('player_id', $durbin->id)
        ->first();
    echo "Drafted: " . ($drafted ? "Yes - Team {$drafted->team_id} ({$drafted->roster_position})" : "No") . "\n";
} else {
    echo "Caleb Durbin not found in database\n";
}

echo "\n=== CHECKING ALL 3B PLAYERS (TOP 10 BY POINTS) ===\n";
$thirdBasemen = \App\Models\Player::where('positions', 'LIKE', '%3B%')
    ->where('is_pitcher', false)
    ->get();

$scoringCalc = app(\App\Services\ScoringCalculator::class);
$thirdBaseData = [];
foreach ($thirdBasemen as $player) {
    $projection = $player->getLatestProjection('fantasypros', 2025);
    if (!$projection) {
        continue; // Skip players without projections
    }

    $points = $scoringCalc->calculatePlayerScore($player, $draft->league, $projection);

    $adpRanking = \App\Models\PlayerRanking::where('player_id', $player->id)
        ->where('source', 'fantasypros_adp')
        ->where('season', 2026)
        ->first();

    $drafted = \App\Models\TeamRoster::where('draft_id', $draft->id)
        ->where('player_id', $player->id)
        ->first();

    $thirdBaseData[] = [
        'name' => $player->name,
        'positions' => $player->positions,
        'points' => $points,
        'adp' => $adpRanking ? $adpRanking->adp : 999,
        'drafted' => $drafted ? "Team {$drafted->team_id}" : "Available"
    ];
}

// Sort by points descending
usort($thirdBaseData, function($a, $b) {
    return $b['points'] <=> $a['points'];
});

// Show top 10
foreach (array_slice($thirdBaseData, 0, 10) as $i => $data) {
    echo "\n" . ($i + 1) . ". {$data['name']} ({$data['positions']})\n";
    echo "   Points: " . round($data['points'], 1) . "\n";
    echo "   ADP: {$data['adp']}\n";
    echo "   Status: {$data['drafted']}\n";
}

