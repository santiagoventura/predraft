<?php

namespace App\Console\Commands;

use App\Models\Player;
use App\Models\PlayerProjection;
use App\Models\PlayerRanking;
use App\Models\PlayerScore;
use App\Models\TeamRoster;
use App\Models\DraftPick;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MergeDuplicatePlayers extends Command
{
    protected $signature = 'players:merge-duplicates 
                            {--dry-run : Show what would be merged without making changes}
                            {--force : Skip confirmation prompts}';

    protected $description = 'Find and merge duplicate players in the database';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('Scanning for duplicate players...');

        $duplicates = $this->findDuplicates();

        if (empty($duplicates)) {
            $this->info('No duplicates found!');
            return self::SUCCESS;
        }

        $this->info('Found ' . count($duplicates) . ' sets of duplicate players:');
        $this->newLine();

        foreach ($duplicates as $normalizedName => $players) {
            $this->line("  <comment>{$normalizedName}</comment>");
            foreach ($players as $player) {
                $projCount = $player->projections()->count();
                $rankCount = $player->rankings()->count();
                $this->line("    - ID {$player->id}: {$player->name} (Projections: {$projCount}, Rankings: {$rankCount})");
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('Dry run mode - no changes made.');
            return self::SUCCESS;
        }

        if (!$force && !$this->confirm('Do you want to merge these duplicates?')) {
            $this->info('Aborted.');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('Merging duplicates...');

        $merged = 0;
        foreach ($duplicates as $normalizedName => $players) {
            $this->mergePlayers($players);
            $merged++;
        }

        $this->info("Successfully merged {$merged} sets of duplicates.");
        return self::SUCCESS;
    }

    protected function findDuplicates(): array
    {
        $players = Player::all();
        $grouped = [];

        foreach ($players as $player) {
            // Skip players explicitly marked as (Batter) or (Pitcher) in their name
            // These are intentional separate entries (e.g., Shohei Ohtani)
            if (preg_match('/\(Batter\)|\(Pitcher\)/i', $player->name)) {
                continue;
            }

            $normalized = $this->normalizeName($player->name);

            // Also group by pitcher status to avoid merging pitcher/batter versions
            $key = $normalized . '|' . ($player->is_pitcher ? 'pitcher' : 'batter');

            if (!isset($grouped[$key])) {
                $grouped[$key] = [];
            }
            $grouped[$key][] = $player;
        }

        // Filter to only groups with more than one player
        return array_filter($grouped, fn($group) => count($group) > 1);
    }

    protected function normalizeName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/\s*\((?:Batter|Pitcher)\)\s*$/i', '', $name);
        $name = preg_replace('/\bJr\.?\b/i', 'Jr', $name);
        $name = preg_replace('/\bSr\.?\b/i', 'Sr', $name);
        $name = str_replace('.', '', $name);
        $name = $this->removeAccents($name);
        $name = strtolower($name);
        $name = preg_replace('/\s+/', ' ', $name);
        return trim($name);
    }

    protected function removeAccents(string $string): string
    {
        $accents = [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o', 'ø' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n', 'ń' => 'n', 'ç' => 'c', 'ý' => 'y', 'ÿ' => 'y',
            'Á' => 'A', 'À' => 'A', 'Ä' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Å' => 'A',
            'É' => 'E', 'È' => 'E', 'Ë' => 'E', 'Ê' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Ï' => 'I', 'Î' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Ö' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ø' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Ü' => 'U', 'Û' => 'U',
            'Ñ' => 'N', 'Ń' => 'N', 'Ç' => 'C', 'Ý' => 'Y', 'Ÿ' => 'Y',
        ];
        return strtr($string, $accents);
    }

    protected function mergePlayers(array $players): void
    {
        // Sort by ID to keep the oldest (lowest ID) as the primary
        usort($players, fn($a, $b) => $a->id <=> $b->id);

        $primary = array_shift($players);
        $this->line("  Keeping: ID {$primary->id} ({$primary->name})");

        DB::transaction(function () use ($primary, $players) {
            foreach ($players as $duplicate) {
                $this->line("    Merging ID {$duplicate->id} into {$primary->id}...");

                // Move projections - handle duplicates by keeping the newer one
                $this->mergeProjections($primary->id, $duplicate->id);

                // Move rankings - handle duplicates by keeping the newer one
                $this->mergeRankings($primary->id, $duplicate->id);

                // Move scores - handle duplicates by keeping the newer one
                $this->mergeScores($primary->id, $duplicate->id);

                // Move roster entries (simple update, no unique constraint issues)
                TeamRoster::where('player_id', $duplicate->id)
                    ->update(['player_id' => $primary->id]);

                // Move draft picks (simple update, no unique constraint issues)
                DraftPick::where('player_id', $duplicate->id)
                    ->update(['player_id' => $primary->id]);

                // Delete the duplicate
                $duplicate->forceDelete();
            }
        });
    }

    /**
     * Merge projections from duplicate to primary, handling unique constraints.
     * Unique constraint: (player_id, season, source)
     */
    protected function mergeProjections(int $primaryId, int $duplicateId): void
    {
        $duplicateProjections = PlayerProjection::where('player_id', $duplicateId)->get();

        foreach ($duplicateProjections as $dupProj) {
            // Check if primary already has this projection
            $existing = PlayerProjection::where('player_id', $primaryId)
                ->where('season', $dupProj->season)
                ->where('source', $dupProj->source)
                ->first();

            if ($existing) {
                // Keep the newer one (by updated_at)
                if ($dupProj->updated_at > $existing->updated_at) {
                    $existing->delete();
                    $dupProj->update(['player_id' => $primaryId]);
                } else {
                    $dupProj->delete();
                }
            } else {
                $dupProj->update(['player_id' => $primaryId]);
            }
        }
    }

    /**
     * Merge rankings from duplicate to primary, handling unique constraints.
     * Unique constraint: (player_id, source, season)
     */
    protected function mergeRankings(int $primaryId, int $duplicateId): void
    {
        $duplicateRankings = PlayerRanking::where('player_id', $duplicateId)->get();

        foreach ($duplicateRankings as $dupRank) {
            $existing = PlayerRanking::where('player_id', $primaryId)
                ->where('source', $dupRank->source)
                ->where('season', $dupRank->season)
                ->first();

            if ($existing) {
                if ($dupRank->updated_at > $existing->updated_at) {
                    $existing->delete();
                    $dupRank->update(['player_id' => $primaryId]);
                } else {
                    $dupRank->delete();
                }
            } else {
                $dupRank->update(['player_id' => $primaryId]);
            }
        }
    }

    /**
     * Merge scores from duplicate to primary, handling unique constraints.
     * Unique constraint: (player_id, league_id, season, projection_source)
     */
    protected function mergeScores(int $primaryId, int $duplicateId): void
    {
        $duplicateScores = PlayerScore::where('player_id', $duplicateId)->get();

        foreach ($duplicateScores as $dupScore) {
            $existing = PlayerScore::where('player_id', $primaryId)
                ->where('league_id', $dupScore->league_id)
                ->where('season', $dupScore->season)
                ->where('projection_source', $dupScore->projection_source)
                ->first();

            if ($existing) {
                if ($dupScore->updated_at > $existing->updated_at) {
                    $existing->delete();
                    $dupScore->update(['player_id' => $primaryId]);
                } else {
                    $dupScore->delete();
                }
            } else {
                $dupScore->update(['player_id' => $primaryId]);
            }
        }
    }
}

