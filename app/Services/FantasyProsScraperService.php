<?php

namespace App\Services;

use App\Models\Player;
use App\Models\PlayerProjection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI-powered service to fetch and parse FanGraphs projection data.
 * Uses Gemini AI to extract structured data from FanGraphs pages.
 *
 * Data source: https://www.fangraphs.com/projections
 */
class FantasyProsScraperService
{
    protected string $geminiApiKey;
    protected string $geminiModel;
    protected string $geminiBaseUrl;

    public function __construct()
    {
        $this->geminiApiKey = config('services.gemini.api_key');
        $this->geminiModel = config('services.gemini.model', 'gemini-2.0-flash-exp');
        $this->geminiBaseUrl = config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
    }

    /**
     * Fetch and import projections from FanGraphs using AI parsing.
     */
    public function fetchAndImportProjections(string $playerType = 'batters', int $season = 2026): array
    {
        try {
            // Step 1: Fetch the HTML from FanGraphs
            $html = $this->fetchFantasyProsPage($playerType, $season);

            // Step 2: Use AI to parse the HTML into structured data
            $parsedData = $this->parseWithAI($html, $playerType);

            // Step 3: Import the parsed data into database
            $result = $this->importParsedData($parsedData, $playerType, $season);

            return $result;
        } catch (\Exception $e) {
            Log::error('Error fetching FanGraphs data', [
                'player_type' => $playerType,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Fetch HTML from FanGraphs projections page.
     */
    protected function fetchFantasyProsPage(string $playerType, int $season): string
    {
        // FanGraphs URLs for projections (using pageitems=2000 to get all players)
        // Using statgroup=fantasy to get fantasy-specific stats like QS, CG, etc.
        $urls = [
            'batters' => "https://www.fangraphs.com/projections?type=fangraphsdc&stats=bat&pos=&team=0&players=0&lg=all&pageitems=2000&statgroup=fantasy&fantasypreset=dashboard",
            'pitchers' => "https://www.fangraphs.com/projections?type=fangraphsdc&stats=pit&pos=all&team=0&players=0&lg=all&pageitems=2000&statgroup=fantasy&fantasypreset=dashboard",
        ];

        $url = $urls[$playerType] ?? $urls['batters'];

        Log::info('Fetching FanGraphs data', ['url' => $url]);

        $response = Http::timeout(30)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])
            ->get($url);

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch FanGraphs page: " . $response->status());
        }

        return $response->body();
    }

    /**
     * Parse HTML to extract embedded JSON data from FanGraphs.
     * FanGraphs uses Next.js and embeds data in __NEXT_DATA__ script tag.
     */
    protected function parseWithAI(string $html, string $playerType): array
    {
        // FanGraphs embeds data in <script id="__NEXT_DATA__"> tag
        if (preg_match('/<script id="__NEXT_DATA__"[^>]*>(.*?)<\/script>/s', $html, $matches)) {
            $jsonData = $matches[1];
            $data = json_decode($jsonData, true);

            // Data is in dehydratedState.queries[0].state.data
            if ($data && isset($data['props']['pageProps']['dehydratedState']['queries'])) {
                foreach ($data['props']['pageProps']['dehydratedState']['queries'] as $query) {
                    if (isset($query['state']['data']) && is_array($query['state']['data'])) {
                        $players = $query['state']['data'];

                        Log::info('Extracted player data from FanGraphs __NEXT_DATA__', [
                            'player_count' => count($players),
                            'player_type' => $playerType,
                        ]);

                        // Convert FanGraphs format to our format
                        return $this->convertFanGraphsData($players, $playerType);
                    }
                }
            }
        }

        // Fallback: If no __NEXT_DATA__ found, return empty array
        Log::warning('Could not find player data in FanGraphs HTML');
        return [];
    }

    /**
     * Convert FanGraphs data format to our internal format.
     */
    protected function convertFanGraphsData(array $players, string $playerType): array
    {
        $converted = [];

        foreach ($players as $player) {
            $item = [
                'player_name' => $player['PlayerName'] ?? null,
                'team' => $player['Team'] ?? null,
                'positions' => $player['minpos'] ?? $player['Pos'] ?? null,
            ];

            if ($playerType === 'batters') {
                $item = array_merge($item, [
                    'pa' => $player['PA'] ?? null,
                    'ab' => $player['AB'] ?? null,
                    'h' => $player['H'] ?? null,
                    '2b' => $player['2B'] ?? null,
                    '3b' => $player['3B'] ?? null,
                    'hr' => $player['HR'] ?? null,
                    'r' => $player['R'] ?? null,
                    'rbi' => $player['RBI'] ?? null,
                    'sb' => $player['SB'] ?? null,
                    'cs' => $player['CS'] ?? null,
                    'bb' => $player['BB'] ?? null,
                    'k' => $player['SO'] ?? $player['K'] ?? null, // FanGraphs uses SO for batter strikeouts
                    'avg' => $player['AVG'] ?? null,
                    'obp' => $player['OBP'] ?? null,
                    'slg' => $player['SLG'] ?? null,
                    'ops' => $player['OPS'] ?? null,
                ]);
            } else {
                // Pitcher stats (fantasy stat group includes QS, CG)
                $item = array_merge($item, [
                    'ip' => $player['IP'] ?? null,
                    'w' => $player['W'] ?? null,
                    'l' => $player['L'] ?? null,
                    'sv' => $player['SV'] ?? null,
                    'hld' => $player['HLD'] ?? null,
                    'k' => $player['K'] ?? $player['SO'] ?? null,
                    'bb' => $player['BB'] ?? null,
                    'era' => $player['ERA'] ?? null,
                    'whip' => $player['WHIP'] ?? null,
                    'k_per_9' => $player['K/9'] ?? null,
                    'bb_per_9' => $player['BB/9'] ?? null,
                    'h' => $player['H'] ?? null,
                    'er' => $player['ER'] ?? null,
                    'qs' => $player['QS'] ?? null,
                    'cg' => $player['CG'] ?? null,
                ]);
            }

            $converted[] = $item;
        }

        return $converted;
    }

    /**
     * Build prompt for AI to parse the HTML.
     */
    protected function buildParsingPrompt(string $html, string $playerType): string
    {
        if ($playerType === 'batters') {
            return $this->buildBattersPrompt($html);
        } else {
            return $this->buildPitchersPrompt($html);
        }
    }

    /**
     * Build prompt for parsing batter projections.
     */
    protected function buildBattersPrompt(string $html): string
    {
        return <<<PROMPT
You are a data extraction expert. This HTML page from FanGraphs contains baseball player projection data.

The data is embedded somewhere in the HTML - either in:
1. JavaScript variables (var data = [...], window.data = [...], etc.)
2. JSON embedded in <script> tags
3. HTML table rows
4. React/Vue component data

HTML CONTENT:
{$html}

TASK:
Extract ALL batter projections and return as JSON array. Search the ENTIRE HTML for player data.

OUTPUT FORMAT (JSON array):
[
  {
    "player_name": "Shohei Ohtani",
    "team": "LAD",
    "positions": "DH",
    "pa": 650,
    "ab": 580,
    "h": 165,
    "2b": 30,
    "3b": 2,
    "hr": 45,
    "r": 100,
    "rbi": 110,
    "sb": 15,
    "bb": 70,
    "avg": 0.284,
    "obp": 0.372,
    "slg": 0.587
  }
]

CRITICAL REQUIREMENTS:
- Extract ALL players (FanGraphs shows 400+ batters)
- Search JavaScript code, JSON data, and HTML tables
- If you find player names but incomplete stats, include them anyway with available data
- All numeric stats must be numbers, not strings
- Batting average, OBP, SLG should be decimals (0.284 not 284)
- Return ONLY the JSON array, no markdown, no explanation
- If no data found, return empty array []
PROMPT;
    }

    /**
     * Build prompt for parsing pitcher projections.
     */
    protected function buildPitchersPrompt(string $html): string
    {
        return <<<PROMPT
You are a data extraction expert. This HTML page from FanGraphs contains baseball pitcher projection data.

The data is embedded somewhere in the HTML - either in:
1. JavaScript variables (var data = [...], window.data = [...], etc.)
2. JSON embedded in <script> tags
3. HTML table rows
4. React/Vue component data

HTML CONTENT:
{$html}

TASK:
Extract ALL pitcher projections and return as JSON array. Search the ENTIRE HTML for player data.

OUTPUT FORMAT (JSON array):
[
  {
    "player_name": "Tarik Skubal",
    "team": "DET",
    "positions": "SP",
    "ip": 200.0,
    "w": 15,
    "l": 8,
    "sv": 0,
    "k": 250,
    "bb": 45,
    "era": 3.20,
    "whip": 1.05,
    "h": 165,
    "er": 71
  }
]

CRITICAL REQUIREMENTS:
- Extract ALL pitchers (FanGraphs shows 300+ pitchers)
- Search JavaScript code, JSON data, and HTML tables
- If you find player names but incomplete stats, include them anyway with available data
- All numeric stats must be numbers, not strings
- ERA, WHIP, IP should be decimals (3.20, 1.05, 200.0)
- Return ONLY the JSON array, no markdown, no explanation
- If no data found, return empty array []
PROMPT;
    }

    /**
     * Extract JSON from AI response (handles markdown code blocks).
     */
    protected function extractJsonFromResponse(string $response): array
    {
        // Remove markdown code blocks if present
        $response = preg_replace('/```json\s*/i', '', $response);
        $response = preg_replace('/```\s*$/i', '', $response);
        $response = trim($response);

        // Try to find JSON array
        if (preg_match('/\[.*\]/s', $response, $matches)) {
            $json = $matches[0];
        } else {
            $json = $response;
        }

        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed to parse AI response as JSON: ' . json_last_error_msg());
        }

        if (!is_array($data)) {
            throw new \Exception('AI response is not a JSON array');
        }

        return $data;
    }

    /**
     * Import parsed data into database.
     */
    protected function importParsedData(array $data, string $playerType, int $season): array
    {
        // If no data was extracted, log warning and return
        if (empty($data)) {
            Log::warning('No player data extracted from FantasyPros - page may require JavaScript rendering');
            return [
                'imported' => 0,
                'updated' => 0,
                'created' => [],
                'not_found' => [],
                'message' => 'No data found - FantasyPros may require JavaScript. Using existing data.',
            ];
        }

        $imported = 0;
        $updated = 0;
        $notFound = [];
        $created = [];

        DB::beginTransaction();
        try {
            foreach ($data as $item) {
                $playerName = $item['player_name'] ?? null;
                if (!$playerName) {
                    continue;
                }

                // Find or create player
                $player = $this->findOrCreatePlayer($item, $playerType);

                if (!$player) {
                    $notFound[] = $playerName;
                    continue;
                }

                // Track if player was just created
                if ($player->wasRecentlyCreated) {
                    $created[] = $playerName;
                }

                // Prepare projection data
                $projectionData = [
                    'player_id' => $player->id,
                    'season' => $season,
                    'source' => 'fangraphs',
                    'imported_at' => now(),
                ];

                if ($playerType === 'pitchers') {
                    $projectionData = array_merge($projectionData, [
                        'ip' => $item['ip'] ?? null,
                        'w' => $item['w'] ?? null,
                        'l' => $item['l'] ?? null,
                        'sv' => $item['sv'] ?? null,
                        'hld' => $item['hld'] ?? $item['holds'] ?? null,
                        'k' => $item['k'] ?? $item['so'] ?? null,
                        'bb' => $item['bb'] ?? null,
                        'era' => $item['era'] ?? null,
                        'whip' => $item['whip'] ?? null,
                        'k_per_9' => $item['k_per_9'] ?? $item['k/9'] ?? null,
                        'bb_per_9' => $item['bb_per_9'] ?? $item['bb/9'] ?? null,
                        'h' => $item['h'] ?? null,
                        'er' => $item['er'] ?? null,
                        'qs' => $item['qs'] ?? null,
                        'cg' => $item['cg'] ?? null,
                    ]);
                } else {
                    $projectionData = array_merge($projectionData, [
                        'pa' => $item['pa'] ?? null,
                        'ab' => $item['ab'] ?? null,
                        'h' => $item['h'] ?? null,
                        'doubles' => $item['2b'] ?? $item['doubles'] ?? null,
                        'triples' => $item['3b'] ?? $item['triples'] ?? null,
                        'hr' => $item['hr'] ?? null,
                        'r' => $item['r'] ?? null,
                        'rbi' => $item['rbi'] ?? null,
                        'sb' => $item['sb'] ?? null,
                        'cs' => $item['cs'] ?? null,
                        'bb' => $item['bb'] ?? null,
                        'k' => $item['k'] ?? $item['so'] ?? null, // Strikeouts
                        'avg' => $item['avg'] ?? $item['ba'] ?? null,
                        'obp' => $item['obp'] ?? null,
                        'slg' => $item['slg'] ?? null,
                        'ops' => $item['ops'] ?? null,
                    ]);
                }

                // Create or update projection
                $projection = PlayerProjection::updateOrCreate(
                    [
                        'player_id' => $player->id,
                        'season' => $season,
                        'source' => 'fangraphs',
                    ],
                    $projectionData
                );

                if ($projection->wasRecentlyCreated) {
                    $imported++;
                } else {
                    $updated++;
                }
            }

            DB::commit();

            return [
                'success' => true,
                'imported' => $imported,
                'updated' => $updated,
                'not_found' => $notFound,
                'created_players' => $created,
                'total_processed' => count($data),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Find or create player from parsed data.
     */
    protected function findOrCreatePlayer(array $data, string $playerType): ?Player
    {
        $playerName = $data['player_name'];
        $team = $data['team'] ?? null;
        $positions = $data['positions'] ?? ($playerType === 'pitchers' ? 'P' : 'UTIL');

        // Try to find existing player with fuzzy matching
        $player = $this->findPlayerByName($playerName, $playerType);

        if ($player) {
            // Update player info with latest data (team changes, position changes, etc.)
            $updateData = [];

            // Update team if we have new data (handles trades/signings)
            if ($team && $team !== $player->mlb_team) {
                $updateData['mlb_team'] = $team;
            }

            // Update positions if we have new data
            if ($positions && $positions !== 'UTIL' && $positions !== $player->positions) {
                $updateData['positions'] = $positions;
                $updateData['primary_position'] = $this->getPrimaryPosition($positions);
            }

            if (!empty($updateData)) {
                $player->update($updateData);
            }

            return $player;
        }

        // Create new player
        $isPitcher = $playerType === 'pitchers' || strpos($positions, 'P') !== false;

        return Player::create([
            'name' => $playerName,
            'mlb_team' => $team,
            'positions' => $positions,
            'primary_position' => $this->getPrimaryPosition($positions),
            'is_pitcher' => $isPitcher,
            'external_id' => null,
        ]);
    }

    /**
     * Normalize a player name for comparison.
     * Removes periods, normalizes suffixes, removes accents, etc.
     */
    protected function normalizeName(string $name): string
    {
        // Trim whitespace
        $name = trim($name);

        // Remove common suffixes in parentheses like "(Batter)" or "(Pitcher)"
        $name = preg_replace('/\s*\((?:Batter|Pitcher)\)\s*$/i', '', $name);

        // Normalize Jr./Jr/Junior, Sr./Sr/Senior, III, II, IV
        $name = preg_replace('/\bJr\.?\b/i', 'Jr', $name);
        $name = preg_replace('/\bSr\.?\b/i', 'Sr', $name);
        $name = preg_replace('/\bJunior\b/i', 'Jr', $name);
        $name = preg_replace('/\bSenior\b/i', 'Sr', $name);

        // Remove all periods
        $name = str_replace('.', '', $name);

        // Normalize accented characters to ASCII equivalents
        $name = $this->removeAccents($name);

        // Convert to lowercase for comparison
        $name = strtolower($name);

        // Normalize multiple spaces to single space
        $name = preg_replace('/\s+/', ' ', $name);

        return trim($name);
    }

    /**
     * Remove accents from a string.
     */
    protected function removeAccents(string $string): string
    {
        $accents = [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o', 'ø' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n', 'ń' => 'n',
            'ç' => 'c',
            'ý' => 'y', 'ÿ' => 'y',
            'Á' => 'A', 'À' => 'A', 'Ä' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Å' => 'A',
            'É' => 'E', 'È' => 'E', 'Ë' => 'E', 'Ê' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Ï' => 'I', 'Î' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Ö' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ø' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Ü' => 'U', 'Û' => 'U',
            'Ñ' => 'N', 'Ń' => 'N',
            'Ç' => 'C',
            'Ý' => 'Y', 'Ÿ' => 'Y',
        ];

        return strtr($string, $accents);
    }

    /**
     * Find a player by name with fuzzy matching.
     */
    protected function findPlayerByName(string $searchName, string $playerType): ?Player
    {
        $normalizedSearch = $this->normalizeName($searchName);
        $isPitcher = $playerType === 'pitchers';

        // First try exact match (case-insensitive)
        $player = Player::whereRaw('LOWER(name) = ?', [strtolower(trim($searchName))])->first();
        if ($player) {
            return $player;
        }

        // Get candidates using LIKE query
        // Extract first and last name parts for better matching
        $nameParts = explode(' ', $searchName);
        $lastName = end($nameParts);
        // Remove Jr, Sr, II, III, IV from last name for matching
        $lastName = preg_replace('/\b(Jr|Sr|II|III|IV)\.?\b/i', '', $lastName);
        $lastName = trim($lastName);

        $candidates = Player::where('name', 'LIKE', '%' . $lastName . '%')->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        // Score each candidate
        $bestMatch = null;
        $bestScore = 0;

        foreach ($candidates as $candidate) {
            $normalizedCandidate = $this->normalizeName($candidate->name);

            // Exact normalized match
            if ($normalizedCandidate === $normalizedSearch) {
                return $candidate;
            }

            // Calculate similarity score
            similar_text($normalizedSearch, $normalizedCandidate, $percent);

            // Bonus for matching player type (pitcher vs batter)
            $typeBonus = 0;
            if ($isPitcher && $candidate->is_pitcher) {
                $typeBonus = 10;
            } elseif (!$isPitcher && !$candidate->is_pitcher) {
                $typeBonus = 10;
            }

            $score = $percent + $typeBonus;

            // Must be at least 80% similar (before type bonus)
            if ($percent >= 80 && $score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $candidate;
            }
        }

        return $bestMatch;
    }

    /**
     * Get primary position from positions string.
     */
    protected function getPrimaryPosition(string $positions): string
    {
        $posArray = explode(',', $positions);

        // Priority order for positions
        $priority = ['C', 'SS', '2B', '3B', '1B', 'OF', 'DH', 'SP', 'RP', 'P', 'UTIL'];

        foreach ($priority as $pos) {
            if (in_array($pos, $posArray)) {
                return $pos;
            }
        }

        return $posArray[0] ?? 'UTIL';
    }
}

