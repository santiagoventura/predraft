<?php

namespace App\Services;

use App\Models\Player;
use App\Models\PlayerRanking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for importing player data from FantasyPros or CSV files.
 * 
 * This service is designed to be flexible and respect external sites' ToS.
 * For now, it works with CSV exports or manually downloaded files.
 * Can be extended to use official APIs when available.
 */
class FantasyProsImporter
{
    /**
     * Import players from a CSV file (like the existing players.csv).
     * 
     * Expected format: id,name team,positions
     * Example: 1,Shohei Ohtani (Batter) LAD,UTIL
     */
    public function importPlayersFromCsv(string $filePath, int $season = 2025): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception("File not found: {$filePath}");
        }

        $imported = 0;
        $updated = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            $file = fopen($filePath, 'r');
            
            while (($line = fgetcsv($file)) !== false) {
                if (count($line) < 3) {
                    continue;
                }

                [$externalId, $nameTeam, $positions] = $line;
                
                // Parse name and team
                $parsed = $this->parsePlayerNameAndTeam($nameTeam);
                
                // Determine if pitcher
                $isPitcher = $this->isPitcherPosition($positions);
                
                // Find or create player
                $player = Player::updateOrCreate(
                    ['external_id' => $externalId],
                    [
                        'name' => $parsed['name'],
                        'mlb_team' => $parsed['team'],
                        'positions' => $positions,
                        'primary_position' => $this->getPrimaryPosition($positions),
                        'is_pitcher' => $isPitcher,
                    ]
                );

                if ($player->wasRecentlyCreated) {
                    $imported++;
                } else {
                    $updated++;
                }
            }

            fclose($file);
            DB::commit();

            return [
                'success' => true,
                'imported' => $imported,
                'updated' => $updated,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error importing players from CSV', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Import rankings from a CSV file (like my_rank.csv or third_rank.csv).
     * 
     * Expected format: rank,name
     * Example: 1,Shohei Ohtani (Batter)
     */
    public function importRankingsFromCsv(
        string $filePath,
        string $source = 'custom',
        int $season = 2025
    ): array {
        if (!file_exists($filePath)) {
            throw new \Exception("File not found: {$filePath}");
        }

        $imported = 0;
        $notFound = [];

        DB::beginTransaction();
        try {
            $file = fopen($filePath, 'r');
            
            while (($line = fgetcsv($file)) !== false) {
                if (count($line) < 2) {
                    continue;
                }

                [$rank, $name] = $line;
                
                // Clean up name for matching
                $cleanName = $this->cleanPlayerName($name);
                
                // Find player by name (fuzzy matching)
                $player = $this->findPlayerByName($cleanName);
                
                if (!$player) {
                    $notFound[] = $name;
                    continue;
                }

                // Create or update ranking
                PlayerRanking::updateOrCreate(
                    [
                        'player_id' => $player->id,
                        'source' => $source,
                        'season' => $season,
                    ],
                    [
                        'overall_rank' => (int) $rank,
                        'imported_at' => now(),
                    ]
                );

                $imported++;
            }

            fclose($file);
            DB::commit();

            return [
                'success' => true,
                'imported' => $imported,
                'not_found' => $notFound,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error importing rankings from CSV', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Parse player name and team from combined string.
     * Example: "Shohei Ohtani (Batter) LAD" -> ['name' => 'Shohei Ohtani', 'team' => 'LAD']
     */
    protected function parsePlayerNameAndTeam(string $nameTeam): array
    {
        // Remove (Batter) or (Pitcher) designation
        $nameTeam = preg_replace('/\s*\((Batter|Pitcher)\)\s*/', ' ', $nameTeam);

        // Split by last space to get team
        $parts = explode(' ', trim($nameTeam));
        $team = array_pop($parts);
        $name = implode(' ', $parts);

        return [
            'name' => trim($name),
            'team' => trim($team),
        ];
    }

    /**
     * Determine if positions indicate a pitcher.
     */
    protected function isPitcherPosition(string $positions): bool
    {
        $pitcherPositions = ['P', 'SP', 'RP'];
        $posArray = explode(',', $positions);

        foreach ($posArray as $pos) {
            if (in_array(trim($pos), $pitcherPositions)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the primary position from a comma-separated list.
     */
    protected function getPrimaryPosition(string $positions): string
    {
        $posArray = explode(',', $positions);
        return trim($posArray[0]);
    }

    /**
     * Normalize accented characters to ASCII equivalents.
     */
    protected function normalizeAccents(string $str): string
    {
        $accents = [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ñ' => 'n',
            'Á' => 'A', 'À' => 'A', 'Ã' => 'A', 'Â' => 'A', 'Ä' => 'A',
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Õ' => 'O', 'Ô' => 'O', 'Ö' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ñ' => 'N',
        ];

        return strtr($str, $accents);
    }

    /**
     * Clean player name for matching.
     */
    protected function cleanPlayerName(string $name): string
    {
        // Remove suffixes like Jr., Sr., III
        $name = preg_replace('/\s+(Jr\.?|Sr\.?|II|III|IV)$/i', '', $name);

        // Remove (Batter) or (Pitcher)
        $name = preg_replace('/\s*\((Batter|Pitcher)\)\s*/', '', $name);

        // Remove extra whitespace
        $name = preg_replace('/\s+/', ' ', $name);

        return trim($name);
    }

    /**
     * Cached players for name matching.
     */
    protected ?array $playerCache = null;

    /**
     * Get or build player cache with normalized names.
     */
    protected function getPlayerCache(): array
    {
        if ($this->playerCache === null) {
            $this->playerCache = [];
            $players = Player::all();
            foreach ($players as $player) {
                $normalizedName = strtolower($this->normalizeAccents($player->name));
                $cleanName = strtolower($this->cleanPlayerName($this->normalizeAccents($player->name)));

                $this->playerCache[$normalizedName] = $player;
                if ($cleanName !== $normalizedName) {
                    $this->playerCache[$cleanName] = $player;
                }
            }
        }
        return $this->playerCache;
    }

    /**
     * Find player by name with fuzzy matching.
     */
    protected function findPlayerByName(string $name): ?Player
    {
        // Try exact match first
        $player = Player::where('name', $name)->first();
        if ($player) {
            return $player;
        }

        // Try case-insensitive match
        $player = Player::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if ($player) {
            return $player;
        }

        // Normalize accents in the search name for comparison
        $normalizedName = strtolower($this->normalizeAccents($name));
        $cleanNormalizedName = strtolower($this->cleanPlayerName($this->normalizeAccents($name)));

        // Check against cached normalized names
        $cache = $this->getPlayerCache();

        if (isset($cache[$normalizedName])) {
            return $cache[$normalizedName];
        }

        if (isset($cache[$cleanNormalizedName])) {
            return $cache[$cleanNormalizedName];
        }

        // Try partial match (for names with/without accents or slight variations)
        $cleanName = str_replace(['.', "'", '-'], '', $name);
        $player = Player::where(function ($query) use ($cleanName, $name) {
            $query->whereRaw('REPLACE(REPLACE(REPLACE(name, ".", ""), "\'", ""), "-", "") LIKE ?', ["%{$cleanName}%"])
                  ->orWhere('name', 'LIKE', "%{$name}%");
        })->first();

        return $player;
    }

    /**
     * Find player by name with position type filtering.
     * Used for ADP import where we need to match batter vs pitcher variants.
     */
    protected function findPlayerByNameAndType(string $name, bool $isPitcherPosition, bool $isBatterPosition): ?Player
    {
        // Normalize accents in the search name for comparison
        $normalizedName = strtolower($this->normalizeAccents($name));
        $cleanNormalizedName = strtolower($this->cleanPlayerName($this->normalizeAccents($name)));

        // Get all matching players from cache
        $cache = $this->getPlayerCache();
        $candidates = [];

        // Collect all potential matches
        if (isset($cache[$normalizedName])) {
            $candidates[] = $cache[$normalizedName];
        }
        if (isset($cache[$cleanNormalizedName]) && !in_array($cache[$cleanNormalizedName], $candidates, true)) {
            $candidates[] = $cache[$cleanNormalizedName];
        }

        // Also search for players with similar normalized names (for cases like Ohtani Batter/Pitcher)
        foreach ($cache as $key => $player) {
            if (strpos($key, $normalizedName) !== false || strpos($key, $cleanNormalizedName) !== false) {
                if (!in_array($player, $candidates, true)) {
                    $candidates[] = $player;
                }
            }
        }

        // If no position type info, return first match
        if (!$isPitcherPosition && !$isBatterPosition) {
            return $candidates[0] ?? $this->findPlayerByName($name);
        }

        // Filter by position type
        foreach ($candidates as $player) {
            if ($isPitcherPosition && $player->is_pitcher) {
                return $player;
            }
            if ($isBatterPosition && !$player->is_pitcher) {
                return $player;
            }
        }

        // Fall back to regular search if no match with correct type
        return $this->findPlayerByName($name);
    }

    /**
     * Import projections from CSV file.
     *
     * Expected CSV format for batters:
     * player_name,team,pa,ab,h,2b,3b,hr,r,rbi,sb,cs,bb,avg,obp,slg,ops
     *
     * Expected CSV format for pitchers:
     * player_name,team,ip,w,l,sv,hld,k,bb,era,whip,k_per_9,bb_per_9,h,er,cg
     */
    public function importProjectionsFromCsv(string $filePath, string $source = 'fantasypros', int $season = 2025): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception("File not found: {$filePath}");
        }

        $imported = 0;
        $updated = 0;
        $notFound = [];

        DB::beginTransaction();
        try {
            $file = fopen($filePath, 'r');

            // Read header
            $header = fgetcsv($file);
            if (!$header) {
                throw new \Exception("Invalid CSV file - no header row");
            }

            // Normalize header
            $header = array_map('strtolower', $header);
            $header = array_map('trim', $header);

            while (($row = fgetcsv($file)) !== false) {
                if (count($row) < 3) {
                    continue;
                }

                // Combine header with row
                $data = array_combine($header, $row);

                // Find player by name
                $playerName = $data['player_name'] ?? $data['name'] ?? null;
                if (!$playerName) {
                    continue;
                }

                $player = Player::where('name', 'LIKE', '%' . trim($playerName) . '%')->first();

                if (!$player) {
                    $notFound[] = $playerName;
                    continue;
                }

                // Prepare projection data based on player type
                $projectionData = [
                    'player_id' => $player->id,
                    'season' => $season,
                    'source' => $source,
                ];

                if ($player->is_pitcher) {
                    // Pitcher projections
                    $projectionData = array_merge($projectionData, [
                        'ip' => $this->parseFloat($data['ip'] ?? null),
                        'w' => $this->parseInt($data['w'] ?? null),
                        'l' => $this->parseInt($data['l'] ?? null),
                        'sv' => $this->parseInt($data['sv'] ?? null),
                        'hld' => $this->parseInt($data['hld'] ?? $data['holds'] ?? null),
                        'k' => $this->parseInt($data['k'] ?? $data['so'] ?? null),
                        'bb' => $this->parseInt($data['bb'] ?? null),
                        'era' => $this->parseFloat($data['era'] ?? null),
                        'whip' => $this->parseFloat($data['whip'] ?? null),
                        'k_per_9' => $this->parseFloat($data['k_per_9'] ?? $data['k/9'] ?? null),
                        'bb_per_9' => $this->parseFloat($data['bb_per_9'] ?? $data['bb/9'] ?? null),
                        'h' => $this->parseInt($data['h'] ?? null),
                        'er' => $this->parseInt($data['er'] ?? null),
                        'cg' => $this->parseInt($data['cg'] ?? null),
                        'shutouts' => $this->parseInt($data['shutouts'] ?? $data['sho'] ?? null),
                    ]);
                } else {
                    // Batter projections
                    $projectionData = array_merge($projectionData, [
                        'pa' => $this->parseInt($data['pa'] ?? null),
                        'ab' => $this->parseInt($data['ab'] ?? null),
                        'h' => $this->parseInt($data['h'] ?? null),
                        'doubles' => $this->parseInt($data['2b'] ?? $data['doubles'] ?? null),
                        'triples' => $this->parseInt($data['3b'] ?? $data['triples'] ?? null),
                        'hr' => $this->parseInt($data['hr'] ?? null),
                        'r' => $this->parseInt($data['r'] ?? null),
                        'rbi' => $this->parseInt($data['rbi'] ?? null),
                        'sb' => $this->parseInt($data['sb'] ?? null),
                        'cs' => $this->parseInt($data['cs'] ?? null),
                        'bb' => $this->parseInt($data['bb'] ?? null),
                        'hbp' => $this->parseInt($data['hbp'] ?? null),
                        'avg' => $this->parseFloat($data['avg'] ?? $data['ba'] ?? null),
                        'obp' => $this->parseFloat($data['obp'] ?? null),
                        'slg' => $this->parseFloat($data['slg'] ?? null),
                        'ops' => $this->parseFloat($data['ops'] ?? null),
                    ]);
                }

                // Create or update projection
                $projection = \App\Models\PlayerProjection::updateOrCreate(
                    [
                        'player_id' => $player->id,
                        'season' => $season,
                        'source' => $source,
                    ],
                    $projectionData
                );

                if ($projection->wasRecentlyCreated) {
                    $imported++;
                } else {
                    $updated++;
                }
            }

            fclose($file);
            DB::commit();

            return [
                'success' => true,
                'imported' => $imported,
                'updated' => $updated,
                'not_found' => $notFound,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error importing projections from CSV', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Parse integer value from CSV.
     */
    protected function parseInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int) $value;
    }

    /**
     * Parse float value from CSV.
     */
    protected function parseFloat($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (float) $value;
    }

    /**
     * Import ADP (Average Draft Position) from a CSV file.
     *
     * Expected CSV format:
     * "RK","PLAYER NAME",TEAM,"POS","BEST","WORST","AVG.","STD.DEV","ECR VS. ADP"
     * "1","Shohei Ohtani",LAD,"DH1","1","2","1.3","0.4","0"
     *
     * The AVG. column is used as the ADP value.
     */
    public function importAdpFromCsv(
        string $filePath,
        string $source = 'fantasypros_adp',
        int $season = 2025
    ): array {
        if (!file_exists($filePath)) {
            throw new \Exception("File not found: {$filePath}");
        }

        $imported = 0;
        $updated = 0;
        $notFound = [];

        DB::beginTransaction();
        try {
            $file = fopen($filePath, 'r');

            // Read header
            $header = fgetcsv($file);
            if (!$header) {
                throw new \Exception("Invalid CSV file - no header row");
            }

            // Normalize header - remove quotes and special characters
            $header = array_map(function ($col) {
                return strtolower(trim(str_replace(['"', '.'], '', $col)));
            }, $header);

            // Map expected columns
            $playerNameIndex = array_search('player name', $header);
            $teamIndex = array_search('team', $header);
            $posIndex = array_search('pos', $header);
            $avgIndex = array_search('avg', $header);
            $rankIndex = array_search('rk', $header);
            $bestIndex = array_search('best', $header);
            $worstIndex = array_search('worst', $header);
            $stdDevIndex = array_search('stddev', $header);
            $ecrVsAdpIndex = array_search('ecr vs adp', $header);

            if ($playerNameIndex === false || $avgIndex === false) {
                throw new \Exception("CSV must contain 'PLAYER NAME' and 'AVG.' columns");
            }

            while (($row = fgetcsv($file)) !== false) {
                if (count($row) <= max($playerNameIndex, $avgIndex)) {
                    continue;
                }

                $playerName = trim($row[$playerNameIndex] ?? '');
                $adpValue = $this->parseFloat($row[$avgIndex] ?? null);
                $team = $teamIndex !== false ? trim($row[$teamIndex] ?? '') : null;
                $position = $posIndex !== false ? trim($row[$posIndex] ?? '') : null;
                $rank = $rankIndex !== false ? $this->parseInt($row[$rankIndex] ?? null) : null;
                $best = $bestIndex !== false ? $this->parseInt($row[$bestIndex] ?? null) : null;
                $worst = $worstIndex !== false ? $this->parseInt($row[$worstIndex] ?? null) : null;
                $stdDev = $stdDevIndex !== false ? $this->parseFloat($row[$stdDevIndex] ?? null) : null;
                $ecrVsAdp = $ecrVsAdpIndex !== false ? $this->parseInt($row[$ecrVsAdpIndex] ?? null) : null;

                if (empty($playerName)) {
                    continue;
                }

                // Clean up player name for matching
                $cleanName = $this->cleanPlayerName($playerName);

                // Determine if position indicates pitcher or batter
                $isPitcherPosition = $position && preg_match('/^(SP|RP)\d*$/i', $position);
                $isBatterPosition = $position && !$isPitcherPosition;

                // Find player by name (fuzzy matching) with position type consideration
                $player = $this->findPlayerByNameAndType($cleanName, $isPitcherPosition, $isBatterPosition);

                // If not found by name, try with team
                if (!$player && $team) {
                    $query = Player::where('name', 'LIKE', "%{$cleanName}%")
                        ->where('mlb_team', $team);

                    // Also filter by position type if available
                    if ($isPitcherPosition) {
                        $query->where('is_pitcher', true);
                    } elseif ($isBatterPosition) {
                        $query->where('is_pitcher', false);
                    }

                    $player = $query->first();
                }

                if (!$player) {
                    $notFound[] = $playerName . ($team ? " ({$team})" : '');
                    continue;
                }

                // Prepare raw data for storage
                $rawData = [
                    'rank' => $rank,
                    'best' => $best,
                    'worst' => $worst,
                    'std_dev' => $stdDev,
                    'ecr_vs_adp' => $ecrVsAdp,
                    'position' => $position,
                ];

                // Create or update ranking with ADP
                $ranking = PlayerRanking::updateOrCreate(
                    [
                        'player_id' => $player->id,
                        'source' => $source,
                        'season' => $season,
                    ],
                    [
                        'adp' => $adpValue,
                        'overall_rank' => $rank,
                        'raw_data' => $rawData,
                        'imported_at' => now(),
                    ]
                );

                if ($ranking->wasRecentlyCreated) {
                    $imported++;
                } else {
                    $updated++;
                }
            }

            fclose($file);
            DB::commit();

            return [
                'success' => true,
                'imported' => $imported,
                'updated' => $updated,
                'not_found' => $notFound,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error importing ADP from CSV', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
