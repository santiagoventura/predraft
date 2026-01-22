<?php

namespace App\Services;

use App\Models\Player;
use App\Models\PlayerInjury;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class InjuryDataService
{
    protected $geminiApiKey;

    public function __construct()
    {
        $this->geminiApiKey = config('services.gemini.api_key');
    }

    /**
     * Fetch injury data from MLB.com official injury report
     * Returns raw HTML for AI to analyze when checking specific players
     */
    public function fetchInjuryData(): array
    {
        try {
            // Check cache first (cache for 2 hours since injuries change frequently)
            $cached = Cache::get('mlb_injury_report_html');
            if ($cached) {
                return ['html' => $cached, 'cached' => true];
            }

            // Fetch from MLB.com official injury report
            $url = 'https://www.mlb.com/injury-report';
            $html = $this->fetchPage($url);

            if ($html) {
                // Cache the HTML for 2 hours
                Cache::put('mlb_injury_report_html', $html, now()->addHours(2));
                Log::info('Fetched fresh injury data from MLB.com');
                return ['html' => $html, 'cached' => false];
            }

            // If fetch failed, return empty
            return ['html' => '', 'cached' => false];

        } catch (\Exception $e) {
            Log::error('Error fetching injury data from MLB.com: ' . $e->getMessage());
            return ['html' => Cache::get('mlb_injury_report_html', ''), 'cached' => true];
        }
    }

    /**
     * Get injury status for a specific player
     * Returns a note to check MLB.com injury report for AI analysis
     */
    public function getPlayerInjuryStatus(string $playerName, int $season = 2026): string
    {
        // Check if we have stored injury data in database (manually added)
        $player = Player::where('name', $playerName)->first();

        // Try fuzzy match if exact fails
        if (!$player) {
            $normalizedSearch = $this->normalizePlayerName($playerName);
            $player = Player::all()->first(function($p) use ($normalizedSearch) {
                return $this->namesMatch($normalizedSearch, $this->normalizePlayerName($p->name));
            });
        }

        if ($player) {
            $injury = $player->getCurrentInjury($season);
            if ($injury) {
                return $injury->getFormattedStatus();
            }
        }

        // Return instruction for AI to check MLB.com
        return 'Check MLB.com injury report and recent news for current injury status and historical injury concerns';
    }

    /**
     * Fetch HTML from injury page
     */
    protected function fetchPage(string $url): ?string
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])
                ->get($url);

            if ($response->successful()) {
                return $response->body();
            }
        } catch (\Exception $e) {
            Log::warning("Failed to fetch {$url}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Normalize player name for matching
     */
    protected function normalizePlayerName(string $name): string
    {
        // Remove accents, convert to lowercase, remove Jr/Sr/III etc
        $name = strtolower($name);
        $name = preg_replace('/\s+(jr\.?|sr\.?|iii|ii|iv)$/i', '', $name);
        $name = preg_replace('/[áàâä]/i', 'a', $name);
        $name = preg_replace('/[éèêë]/i', 'e', $name);
        $name = preg_replace('/[íìîï]/i', 'i', $name);
        $name = preg_replace('/[óòôö]/i', 'o', $name);
        $name = preg_replace('/[úùûü]/i', 'u', $name);
        $name = preg_replace('/ñ/i', 'n', $name);
        
        return trim($name);
    }

    /**
     * Check if two player names match (fuzzy)
     */
    protected function namesMatch(string $name1, string $name2): bool
    {
        // Exact match
        if ($name1 === $name2) {
            return true;
        }
        
        // Check if one contains the other
        if (strpos($name1, $name2) !== false || strpos($name2, $name1) !== false) {
            return true;
        }
        
        // Split into parts and check
        $parts1 = explode(' ', $name1);
        $parts2 = explode(' ', $name2);
        
        // Last name match + first initial
        if (count($parts1) >= 2 && count($parts2) >= 2) {
            $lastName1 = end($parts1);
            $lastName2 = end($parts2);
            $firstInitial1 = substr($parts1[0], 0, 1);
            $firstInitial2 = substr($parts2[0], 0, 1);
            
            if ($lastName1 === $lastName2 && $firstInitial1 === $firstInitial2) {
                return true;
            }
        }
        
        return false;
    }
}

