<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Models\LeaguePosition;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeagueController extends Controller
{
    /**
     * Display a listing of leagues.
     */
    public function index()
    {
        $leagues = League::with('teams')->latest()->get();
        return view('leagues.index', compact('leagues'));
    }

    /**
     * Show the form for creating a new league.
     */
    public function create()
    {
        $defaultPositions = League::getDefaultRosterConfig();
        return view('leagues.create', compact('defaultPositions'));
    }

    /**
     * Store a newly created league.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'num_teams' => 'required|integer|min:2|max:20',
            'scoring_format' => 'required|in:roto,h2h_categories,h2h_points',
            'positions' => 'required|array',
            'positions.*.position_code' => 'required|string',
            'positions.*.slot_count' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Create league
            $league = League::create([
                'name' => $validated['name'],
                'num_teams' => $validated['num_teams'],
                'scoring_format' => $validated['scoring_format'],
            ]);

            // Create positions
            foreach ($validated['positions'] as $index => $position) {
                if ($position['slot_count'] > 0) {
                    LeaguePosition::create([
                        'league_id' => $league->id,
                        'position_code' => $position['position_code'],
                        'slot_count' => $position['slot_count'],
                        'display_order' => $index + 1,
                    ]);
                }
            }

            // Create teams
            for ($i = 1; $i <= $validated['num_teams']; $i++) {
                Team::create([
                    'league_id' => $league->id,
                    'name' => "Team {$i}",
                    'draft_slot' => $i,
                ]);
            }

            DB::commit();

            return redirect()->route('leagues.show', $league)
                ->with('success', 'League created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create league: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified league.
     */
    public function show(League $league)
    {
        $league->load(['positions', 'teams', 'drafts']);
        return view('leagues.show', compact('league'));
    }

    /**
     * Show the form for editing the league.
     */
    public function edit(League $league)
    {
        $league->load(['positions', 'teams']);
        return view('leagues.edit', compact('league'));
    }

    /**
     * Update the specified league.
     */
    public function update(Request $request, League $league)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'num_teams' => 'required|integer|min:2|max:20',
            'scoring_format' => 'required|in:roto,h2h_categories,h2h_points',
            'positions' => 'required|array',
            'positions.*.position_code' => 'required|string',
            'positions.*.slot_count' => 'required|integer|min:0|max:20',
        ]);

        $league->update([
            'name' => $validated['name'],
            'num_teams' => $validated['num_teams'],
            'scoring_format' => $validated['scoring_format'],
        ]);

        // Update positions
        foreach ($validated['positions'] as $positionData) {
            $league->positions()
                ->where('position_code', $positionData['position_code'])
                ->update(['slot_count' => $positionData['slot_count']]);
        }

        return redirect()->route('leagues.show', $league)
            ->with('success', 'League updated successfully!');
    }

    /**
     * Remove the specified league.
     */
    public function destroy(League $league)
    {
        $league->delete();

        return redirect()->route('leagues.index')
            ->with('success', 'League deleted successfully!');
    }
}

