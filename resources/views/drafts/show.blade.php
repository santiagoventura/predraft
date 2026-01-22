@extends('layouts.app')

@section('title', $draft->name)

@push('styles')
<style>
    @keyframes pulse-once {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
    .animate-pulse-once {
        animation: pulse-once 0.5s ease-in-out 2;
    }
    .simulation-pick-highlight {
        transition: background-color 0.5s ease-out;
    }
</style>
@endpush

@section('content')
<div x-data="draftBoard()" x-init="init()" data-completed-picks="{{ $summary['completed_picks'] }}">
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold">{{ $draft->name }}</h1>
                <p class="text-gray-600">{{ $draft->league->name }}</p>
            </div>
            <div class="flex items-center space-x-4">
                @if($draft->isInProgress())
                    <button @click="autoRefresh = !autoRefresh"
                            class="px-4 py-2 rounded text-sm font-medium transition-colors"
                            :class="autoRefresh ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'">
                        <span x-show="autoRefresh">🔄 Auto-Refresh ON</span>
                        <span x-show="!autoRefresh">⏸️ Auto-Refresh OFF</span>
                    </button>
                @endif
                <span class="px-4 py-2 rounded font-semibold
                    @if($draft->status === 'completed') bg-green-100 text-green-800
                    @elseif($draft->status === 'in_progress') bg-blue-100 text-blue-800
                    @else bg-gray-100 text-gray-800
                    @endif">
                    {{ ucfirst($draft->status) }}
                </span>
                @if($draft->status === 'setup')
                    <form action="{{ route('drafts.start', $draft) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">
                            Start Draft
                        </button>
                    </form>
                @endif
                <form action="{{ route('drafts.destroy', $draft) }}"
                      method="POST"
                      onsubmit="return confirm('Are you sure you want to delete this draft?\n\nThis will permanently delete:\n- All draft picks\n- All team rosters\n- All draft data\n\nThis action cannot be undone!');"
                      class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 text-sm">
                        🗑️ Delete Draft
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-6 gap-6 mb-6">
        <!-- Player Rankings by Points (3 columns = half) -->
        <div class="lg:col-span-3 bg-white rounded-lg shadow p-6" x-data="playerRankings()">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">📊 Rankings by Points</h3>
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-600">Sort by:</label>
                    <select x-model="sortBy" class="text-sm border rounded px-2 py-1">
                        <option value="points">Points</option>
                        <option value="adp">ADP</option>
                        <option value="name">Name</option>
                    </select>
                    <button @click="sortDir = sortDir === 'asc' ? 'desc' : 'asc'"
                            class="text-sm px-2 py-1 border rounded hover:bg-gray-100"
                            :title="sortDir === 'asc' ? 'Ascending' : 'Descending'">
                        <span x-show="sortDir === 'asc'">▲</span>
                        <span x-show="sortDir === 'desc'">▼</span>
                    </button>
                </div>
            </div>

            <!-- Position Filters -->
            <div class="flex flex-wrap gap-2 mb-4">
                <button @click="filterPosition = 'ALL'"
                        :class="filterPosition === 'ALL' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                        class="px-3 py-1 rounded text-sm font-medium hover:opacity-80">
                    All
                </button>
                <button @click="filterPosition = 'BATTERS'"
                        :class="filterPosition === 'BATTERS' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700'"
                        class="px-3 py-1 rounded text-sm font-medium hover:opacity-80">
                    Batters
                </button>
                <button @click="filterPosition = 'PITCHERS'"
                        :class="filterPosition === 'PITCHERS' ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700'"
                        class="px-3 py-1 rounded text-sm font-medium hover:opacity-80">
                    Pitchers
                </button>
                <button @click="filterPosition = 'C'"
                        :class="filterPosition === 'C' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700'"
                        class="px-3 py-1 rounded text-sm font-medium hover:opacity-80">
                    C
                </button>
                <button @click="filterPosition = '1B'"
                        :class="filterPosition === '1B' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700'"
                        class="px-3 py-1 rounded text-sm font-medium hover:opacity-80">
                    1B
                </button>
                <button @click="filterPosition = '2B'"
                        :class="filterPosition === '2B' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700'"
                        class="px-3 py-1 rounded text-sm font-medium hover:opacity-80">
                    2B
                </button>
                <button @click="filterPosition = 'SS'"
                        :class="filterPosition === 'SS' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700'"
                        class="px-3 py-1 rounded text-sm font-medium hover:opacity-80">
                    SS
                </button>
                <button @click="filterPosition = '3B'"
                        :class="filterPosition === '3B' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700'"
                        class="px-3 py-1 rounded text-sm font-medium hover:opacity-80">
                    3B
                </button>
                <button @click="filterPosition = 'OF'"
                        :class="filterPosition === 'OF' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700'"
                        class="px-3 py-1 rounded text-sm font-medium hover:opacity-80">
                    OF
                </button>
                <button @click="filterPosition = 'SP'"
                        :class="filterPosition === 'SP' ? 'bg-purple-500 text-white' : 'bg-gray-200 text-gray-700'"
                        class="px-3 py-1 rounded text-sm font-medium hover:opacity-80">
                    SP
                </button>
                <button @click="filterPosition = 'RP'"
                        :class="filterPosition === 'RP' ? 'bg-purple-500 text-white' : 'bg-gray-200 text-gray-700'"
                        class="px-3 py-1 rounded text-sm font-medium hover:opacity-80">
                    RP
                </button>
            </div>

            <!-- Player List -->
            <div class="overflow-y-auto max-h-64 border rounded">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer hover:text-gray-700"
                                @click="sortBy = 'name'">
                                Player
                                <span x-show="sortBy === 'name'" class="ml-1" x-text="sortDir === 'asc' ? '▲' : '▼'"></span>
                            </th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Pos</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-orange-500 uppercase cursor-pointer hover:text-orange-700"
                                @click="sortBy = 'adp'">
                                ADP
                                <span x-show="sortBy === 'adp'" class="ml-1" x-text="sortDir === 'asc' ? '▲' : '▼'"></span>
                            </th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase cursor-pointer hover:text-gray-700"
                                @click="sortBy = 'points'">
                                Points
                                <span x-show="sortBy === 'points'" class="ml-1" x-text="sortDir === 'asc' ? '▲' : '▼'"></span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="(player, index) in filteredPlayers" :key="player.id">
                            <tr class="hover:bg-gray-50 cursor-pointer" @click="quickDraft(player)">
                                <td class="px-3 py-2 text-sm text-gray-500" x-text="index + 1"></td>
                                <td class="px-3 py-2">
                                    <div class="text-sm font-medium text-gray-900" x-text="player.name"></div>
                                    <div class="text-xs text-gray-500" x-text="player.mlb_team"></div>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="text-xs px-2 py-1 rounded"
                                          :class="player.is_pitcher ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'"
                                          x-text="player.positions"></span>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <span class="text-sm font-semibold text-orange-600" x-text="player.adp ? player.adp.toFixed(1) : '-'"></span>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <span class="text-sm font-bold text-green-600" x-text="player.points ? player.points.toFixed(1) : '-'"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-gray-500 mt-2">Click a player to draft them</p>
        </div>

        <!-- Draft Progress (1 column = 1/6 of total, 1/3 of remaining half) -->
        <div class="lg:col-span-1 bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold mb-4">Draft Progress</h3>
            <dl class="space-y-2">
                <div>
                    <dt class="text-gray-600 text-sm">Round</dt>
                    <dd class="text-2xl font-bold">{{ $draft->current_round }} / {{ $draft->total_rounds }}</dd>
                </div>
                <div>
                    <dt class="text-gray-600 text-sm">Picks</dt>
                    <dd class="text-lg font-semibold">{{ $summary['completed_picks'] }} / {{ $summary['total_picks'] }}</dd>
                </div>
                <div class="pt-2 border-t">
                    <div class="text-sm">
                        <span>Hitters: {{ $summary['hitters_picked'] }}</span>
                    </div>
                    <div class="text-sm">
                        <span>Pitchers: {{ $summary['pitchers_picked'] }}</span>
                    </div>
                </div>
            </dl>
        </div>

        @if($draft->isInProgress() && $currentPick)
            <!-- On the Clock (2 columns = 2/6 of total, 2/3 of remaining half) -->
            <div class="lg:col-span-2 bg-blue-50 border-2 border-blue-500 rounded-lg shadow p-6">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <h3 class="text-lg font-bold">On the Clock</h3>
                        <p class="text-2xl font-bold text-blue-600 mt-2">
                            {{ $currentPick->team->name }} (Pick #{{ $currentPick->overall_pick }})
                        </p>
                    </div>
                    @if($summary['completed_picks'] > 0)
                        <button @click="revertLastPick()"
                                class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 text-sm">
                            ↶ Revert Last Pick
                        </button>
                    @endif
                </div>

                <div class="space-y-4 mt-4">
                    <!-- AI Recommendations Button -->
                    <div>
                        <button @click="loadRecommendations()"
                                :disabled="loading"
                                class="bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700 disabled:opacity-50">
                            <span x-show="!loading">🤖 Get AI Recommendations</span>
                            <span x-show="loading">Loading...</span>
                        </button>
                    </div>

                    <!-- Manual Player Selection -->
                    <div class="border-t pt-4">
                        <h4 class="font-semibold mb-2">Or Select Player Manually:</h4>
                        <form @submit.prevent="manualPick()" class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Search Player</label>
                                <select id="player-select" name="player_id" x-model="selectedPlayerId" class="w-full" required>
                                    <option value="">Type to search...</option>
                                </select>
                            </div>
                            <div x-show="selectedPlayerId" class="text-sm text-gray-600 bg-blue-50 p-3 rounded">
                                <span class="font-medium">ℹ️ Auto-Position:</span>
                                The system will automatically assign this player to the first available eligible position on your roster.
                            </div>
                            <button type="submit"
                                    x-show="selectedPlayerId"
                                    class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">
                                Draft Selected Player
                            </button>
                        </form>
                    </div>

                    <!-- AI Draft Simulation -->
                    <div class="border-t pt-4">
                        <h4 class="font-semibold mb-2">🚀 Auto-Simulate Draft Rounds:</h4>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <label class="text-sm font-medium text-gray-700">Simulate through round:</label>
                                <select x-model="simulateStopRound" class="border rounded px-3 py-2 text-sm w-24">
                                    @for($r = $draft->current_round; $r <= $draft->total_rounds; $r++)
                                        <option value="{{ $r }}">{{ $r }}</option>
                                    @endfor
                                </select>
                                <span class="text-sm text-gray-500">(of {{ $draft->total_rounds }})</span>
                            </div>
                            <button @click="startSimulation()"
                                    :disabled="simulating"
                                    class="bg-purple-600 text-white px-6 py-2 rounded hover:bg-purple-700 disabled:opacity-50 flex items-center gap-2">
                                <span x-show="!simulating">🤖 Start AI Simulation</span>
                                <span x-show="simulating" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Simulating...
                                </span>
                            </button>

                            <!-- Simulation Progress -->
                            <div x-show="simulating || simulationResults.length > 0" class="bg-white rounded-lg p-4 border">
                                <div x-show="simulating" class="mb-3">
                                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                                        <span>Progress</span>
                                        <span x-text="simulationProgress + '%'"></span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-purple-600 h-2 rounded-full transition-all duration-300"
                                             :style="'width: ' + simulationProgress + '%'"></div>
                                    </div>
                                </div>

                                <div x-show="simulationResults.length > 0" class="max-h-40 overflow-y-auto">
                                    <h5 class="font-semibold text-sm text-gray-700 mb-2">Recent Picks:</h5>
                                    <template x-for="(result, index) in simulationResults.slice(-10).reverse()" :key="index">
                                        <div class="text-xs py-1 border-b last:border-0 flex justify-between">
                                            <span>
                                                <span class="font-semibold" x-text="'#' + result.overall_pick"></span>
                                                <span x-text="result.player"></span>
                                                <span class="text-gray-500" x-text="'(' + result.positions + ')'"></span>
                                            </span>
                                            <span class="text-gray-500" x-text="result.team"></span>
                                        </div>
                                    </template>
                                </div>

                                <div x-show="simulationComplete && !simulating" class="mt-3 p-2 bg-green-100 text-green-800 rounded text-sm">
                                    ✅ Simulation complete! <span x-text="simulationResults.length"></span> picks made.
                                    <button @click="window.location.reload()" class="underline ml-2">Refresh page</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- AI Recommendations -->
    <div x-show="recommendations.length > 0" class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-xl font-bold mb-4">🤖 AI Recommendations</h3>
        <div class="space-y-4">
            <template x-for="(rec, index) in recommendations" :key="rec.player_id">
                <div class="border-2 rounded-lg p-5 hover:shadow-lg transition-shadow"
                     :class="rec.is_pitcher ? 'border-purple-200 bg-purple-50' : 'border-blue-200 bg-blue-50'">
                    <!-- Header -->
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="inline-block w-8 h-8 text-white rounded-full text-center leading-8 font-bold"
                                      :class="rec.is_pitcher ? 'bg-purple-600' : 'bg-blue-600'"
                                      x-text="index + 1"></span>
                                <span class="text-xl font-bold" x-text="rec.player_name"></span>
                                <span class="text-gray-600" x-text="'(' + rec.player_team + ')'"></span>
                            </div>
                            <div class="flex items-center gap-2 ml-10">
                                <span class="text-sm font-semibold px-2 py-1 rounded"
                                      :class="rec.is_pitcher ? 'bg-purple-200 text-purple-800' : 'bg-blue-200 text-blue-800'"
                                      x-text="rec.positions"></span>
                                <span x-show="rec.adp" class="text-sm font-bold text-orange-600">
                                    ADP: <span x-text="rec.adp.toFixed(1)"></span>
                                </span>
                                <span x-show="rec.projected_points" class="text-sm font-bold text-green-700">
                                    <span x-text="rec.projected_points"></span> pts projected
                                </span>
                            </div>
                        </div>
                        <button @click="selectPlayer(rec)"
                                class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 font-semibold shadow">
                            Draft This Player
                        </button>
                    </div>

                    <!-- Injury Status -->
                    <div x-show="rec.injury_status" class="mb-3 p-2 rounded"
                         :class="rec.injury_status && rec.injury_status.toLowerCase().includes('healthy') ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'">
                        <span class="font-semibold">🏥 Health: </span>
                        <span x-text="rec.injury_status"></span>
                    </div>

                    <!-- Pros and Cons -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                        <!-- Pros -->
                        <div x-show="rec.pros && rec.pros.length > 0" class="bg-white rounded p-3">
                            <div class="font-semibold text-green-700 mb-2">✅ Pros:</div>
                            <ul class="space-y-1">
                                <template x-for="pro in rec.pros" :key="pro">
                                    <li class="text-sm text-gray-700">• <span x-text="pro"></span></li>
                                </template>
                            </ul>
                        </div>

                        <!-- Cons -->
                        <div x-show="rec.cons && rec.cons.length > 0" class="bg-white rounded p-3">
                            <div class="font-semibold text-red-700 mb-2">⚠️ Cons:</div>
                            <ul class="space-y-1">
                                <template x-for="con in rec.cons" :key="con">
                                    <li class="text-sm text-gray-700">• <span x-text="con"></span></li>
                                </template>
                            </ul>
                        </div>
                    </div>

                    <!-- Position Context -->
                    <div x-show="rec.position_context" class="mb-3 p-2 bg-white rounded border-l-4 border-blue-500">
                        <span class="font-semibold text-blue-700">📊 Position Analysis: </span>
                        <span class="text-sm text-gray-700" x-text="rec.position_context"></span>
                    </div>

                    <!-- Main Explanation -->
                    <div class="p-3 bg-white rounded border-l-4 border-gray-400">
                        <span class="font-semibold text-gray-700">💡 Why This Pick: </span>
                        <span class="text-gray-700" x-text="rec.explanation"></span>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Draft Board - Team Rosters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-xl font-bold mb-4">📋 Draft Board - Team Rosters</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50 z-10">
                            Position
                        </th>
                        @foreach($draft->league->teams as $team)
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[150px]
                                {{ $currentPick && $currentPick->team_id === $team->id ? 'bg-blue-100' : '' }}">
                                <div class="flex items-center space-x-2">
                                    <span>{{ $team->name }}</span>
                                    @if($currentPick && $currentPick->team_id === $team->id)
                                        <span class="inline-block w-2 h-2 bg-blue-600 rounded-full animate-pulse"></span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-400 font-normal">Slot {{ $team->draft_slot }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @php
                        $positions = $draft->league->positions;
                        $positionSlots = [];

                        // Build position slots array (e.g., C, 1B, 2B, SS, 3B, OF1, OF2, OF3, UTIL1, UTIL2, UTIL3, P1-P11)
                        foreach ($positions as $position) {
                            if ($position->slot_count == 1) {
                                $positionSlots[] = $position->position_code;
                            } else {
                                for ($i = 1; $i <= $position->slot_count; $i++) {
                                    $positionSlots[] = $position->position_code . $i;
                                }
                            }
                        }
                    @endphp

                    @foreach($positionSlots as $positionSlot)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 sticky left-0 bg-white z-10">
                                {{ $positionSlot }}
                            </td>
                            @foreach($draft->league->teams as $team)
                                @php
                                    $rosterEntry = $teamRosters[$team->id]->firstWhere('roster_position', $positionSlot);
                                @endphp
                                <td class="px-4 py-3 whitespace-nowrap text-sm
                                    {{ $currentPick && $currentPick->team_id === $team->id ? 'bg-blue-50' : '' }}"
                                    id="roster-cell-{{ $team->id }}-{{ $positionSlot }}"
                                    data-team-id="{{ $team->id }}"
                                    data-position="{{ $positionSlot }}">
                                    @if($rosterEntry)
                                        <div class="flex flex-col">
                                            <span class="font-semibold text-gray-900">{{ $rosterEntry->player->name }}</span>
                                            <span class="text-xs text-gray-500">{{ $rosterEntry->player->mlb_team }} - {{ $rosterEntry->player->positions }}</span>
                                        </div>
                                    @else
                                        <span class="text-gray-400 italic">-</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Legend -->
        <div class="mt-4 flex items-center space-x-4 text-sm text-gray-600">
            <div class="flex items-center space-x-2">
                <span class="inline-block w-2 h-2 bg-blue-600 rounded-full animate-pulse"></span>
                <span>On the Clock</span>
            </div>
            <div class="flex items-center space-x-2">
                <span class="inline-block w-4 h-4 bg-blue-50 border border-blue-200"></span>
                <span>Current Team Column</span>
            </div>
        </div>
    </div>

    <!-- Recent Picks -->
    <div class="bg-white rounded-lg shadow p-6" id="recent-picks-container">
        <h3 class="text-lg font-bold mb-4">Recent Picks</h3>
        <div class="space-y-2" id="recent-picks-list">
            @forelse($recentPicks as $pick)
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded pick-item" data-pick-id="{{ $pick->overall_pick }}">
                    <div>
                        <span class="font-semibold">Pick #{{ $pick->overall_pick }}:</span>
                        <span class="ml-2">{{ $pick->player->name }}</span>
                        <span class="text-gray-600 ml-2">({{ $pick->player->positions }})</span>
                    </div>
                    <div class="text-sm text-gray-600">
                        {{ $pick->team->name }}
                    </div>
                </div>
            @empty
                <div class="text-gray-500 italic" id="no-picks-message">No picks yet</div>
            @endforelse
        </div>
    </div>
</div>

@push('scripts')
<script>
// Available players data with points
const availablePlayersData = @json($availablePlayersJson);

function draftBoard() {
    return {
        loading: false,
        recommendations: [],
        selectedPlayerId: '',
        autoRefresh: true,
        lastPickCount: {{ $summary['completed_picks'] }},
        // Simulation state
        simulating: false,
        simulateStopRound: {{ $draft->current_round }},
        simulationProgress: 0,
        simulationResults: [],
        simulationComplete: false,

        init() {
            // Initialize Select2
            this.initSelect2();

            // Start auto-refresh polling if draft is in progress
            @if($draft->isInProgress())
                this.startAutoRefresh();
            @endif
        },

        startAutoRefresh() {
            // Poll every 5 seconds to check for new picks
            setInterval(() => {
                if (this.autoRefresh && !this.loading) {
                    this.checkForUpdates();
                }
            }, 5000);
        },

        async checkForUpdates() {
            try {
                const response = await fetch('{{ route('drafts.show', $draft) }}', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const html = await response.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    // Check if pick count changed
                    const newPickCount = parseInt(doc.querySelector('[data-completed-picks]')?.dataset.completedPicks || 0);

                    if (newPickCount > this.lastPickCount) {
                        // New pick detected - reload page
                        window.location.reload();
                    }
                }
            } catch (error) {
                console.error('Error checking for updates:', error);
            }
        },

        initSelect2() {
            const self = this;

            $('#player-select').select2({
                placeholder: 'Type player name, team, or position...',
                allowClear: true,
                width: '100%',
                data: availablePlayersData.map(p => ({
                    id: p.id,
                    text: `${p.name} (${p.mlb_team}) - ${p.positions}`,
                    player: p
                })),
                templateResult: function(data) {
                    if (!data.player) return data.text;

                    const badgeClass = data.player.is_pitcher ? 'badge-pitcher' : 'badge-batter';
                    const badgeText = data.player.is_pitcher ? 'P' : 'Batter';

                    const $result = $(`
                        <div class="select2-player-result">
                            <div style="font-weight: 600; margin-bottom: 2px;">${data.player.name}</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">
                                ${data.player.mlb_team} | ${data.player.positions}
                                <span class="badge ${badgeClass}" style="margin-left: 8px;">${badgeText}</span>
                            </div>
                        </div>
                    `);
                    return $result;
                },
                templateSelection: function(data) {
                    if (!data.player) return data.text;
                    return `${data.player.name} (${data.player.mlb_team})`;
                }
            }).on('change', function(e) {
                const playerId = $(this).val();
                self.selectedPlayerId = playerId;
            });
        },

        async loadRecommendations() {
            this.loading = true;
            try {
                const response = await fetch('{{ route('drafts.recommendations', $draft) }}');
                const data = await response.json();

                if (data.success && data.recommendations) {
                    this.recommendations = data.recommendations;
                }
            } catch (error) {
                console.error('Error loading recommendations:', error);
                alert('Failed to load recommendations');
            } finally {
                this.loading = false;
            }
        },

        async selectPlayer(rec) {
            if (!confirm(`Draft ${rec.player_name}?\n\nThe system will automatically assign them to the first available eligible position.`)) {
                return;
            }

            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('player_id', rec.player_id);
            // position_filled is now optional - backend will auto-determine
            formData.append('ai_explanation', rec.explanation);

            try {
                const response = await fetch('{{ route('drafts.pick', $draft) }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                if (response.ok) {
                    const data = await response.json();
                    // Show which position was filled
                    if (data.position_filled) {
                        alert(`✓ Drafted ${rec.player_name} to position ${data.position_filled}!`);
                    }
                    window.location.reload();
                } else {
                    let errorMessage = 'Unknown error';
                    try {
                        const data = await response.json();
                        errorMessage = data.error || data.message || 'Unknown error';
                    } catch (e) {
                        const text = await response.text();
                        console.error('Server response:', text);
                        errorMessage = 'Server error - check console for details';
                    }
                    alert('Failed to make pick: ' + errorMessage);
                }
            } catch (error) {
                console.error('Error making pick:', error);
                alert('Failed to make pick: ' + error.message);
            }
        },

        async manualPick() {
            if (!this.selectedPlayerId) {
                alert('Please select a player');
                return;
            }

            const player = availablePlayersData.find(p => p.id == this.selectedPlayerId);
            if (!player) {
                alert('Player not found');
                return;
            }

            if (!confirm(`Draft ${player.name}?\n\nThe system will automatically assign them to the first available eligible position.`)) {
                return;
            }

            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('player_id', this.selectedPlayerId);
            // position_filled is now optional - backend will auto-determine

            try {
                const response = await fetch('{{ route('drafts.pick', $draft) }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                if (response.ok) {
                    const data = await response.json();
                    // Show which position was filled
                    if (data.position_filled) {
                        alert(`✓ Drafted ${player.name} to position ${data.position_filled}!`);
                    }
                    window.location.reload();
                } else {
                    let errorMessage = 'Unknown error';
                    try {
                        const data = await response.json();
                        errorMessage = data.error || data.message || 'Unknown error';
                    } catch (e) {
                        const text = await response.text();
                        console.error('Server response:', text);
                        errorMessage = 'Server error - check console for details';
                    }
                    alert('Failed to make pick: ' + errorMessage);
                }
            } catch (error) {
                console.error('Error making pick:', error);
                alert('Failed to make pick: ' + error.message);
            }
        },

        async revertLastPick() {
            if (!confirm('Are you sure you want to revert the last pick?\n\nThis will undo the most recent draft selection.')) {
                return;
            }

            try {
                const response = await fetch('{{ route('drafts.revert', $draft) }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        '_token': '{{ csrf_token() }}'
                    })
                });

                if (response.ok) {
                    const data = await response.json();
                    alert('✓ Last pick reverted successfully!');
                    window.location.reload();
                } else {
                    let errorMessage = 'Unknown error';
                    try {
                        const data = await response.json();
                        errorMessage = data.error || data.message || 'Unknown error';
                    } catch (e) {
                        const text = await response.text();
                        console.error('Server response:', text);
                        errorMessage = 'Server error - check console for details';
                    }
                    alert('Failed to revert pick: ' + errorMessage);
                }
            } catch (error) {
                console.error('Error reverting pick:', error);
                alert('Failed to revert pick: ' + error.message);
            }
        },

        async startSimulation() {
            if (!confirm(`Start AI simulation through round ${this.simulateStopRound}?\n\nThe AI will automatically make picks for all teams until the end of round ${this.simulateStopRound}.\n\nThis may take a few minutes.`)) {
                return;
            }

            this.simulating = true;
            this.simulationProgress = 0;
            this.simulationResults = [];
            this.simulationComplete = false;
            this.autoRefresh = false; // Disable auto-refresh during simulation

            // Calculate estimated total picks
            const numTeams = {{ $draft->league->teams()->count() }};
            const currentRound = {{ $draft->current_round }};
            const currentPick = {{ $draft->current_pick }};
            const stopRound = parseInt(this.simulateStopRound);

            // Estimate total picks (rough estimate for progress bar)
            let estimatedPicks = ((stopRound - currentRound + 1) * numTeams) - (currentPick - 1);
            if (estimatedPicks < 1) estimatedPicks = 1;

            let picksMade = 0;

            try {
                // Make picks one at a time
                while (true) {
                    const response = await fetch('{{ route('drafts.simulate', $draft) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            stop_round: stopRound
                        })
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        alert('Simulation failed: ' + (data.error || 'Unknown error'));
                        break;
                    }

                    if (data.done) {
                        // Simulation complete
                        this.simulationComplete = true;
                        this.simulationProgress = 100;
                        console.log('Simulation complete:', data.message);
                        break;
                    }

                    if (data.success && data.pick) {
                        picksMade++;
                        this.simulationProgress = Math.min(Math.round((picksMade / estimatedPicks) * 100), 99);
                        this.simulationResults.push(data.pick);
                        this.updateDraftBoardWithPick(data.pick);
                        this.updateRecentPicksWithPick(data.pick);
                        this.removePlayerFromAvailable(data.pick.player_id);

                        // Small delay to make the animation visible
                        await new Promise(resolve => setTimeout(resolve, 100));
                    } else {
                        alert('Simulation error: ' + (data.error || 'Unknown error'));
                        break;
                    }
                }

            } catch (error) {
                console.error('Error during simulation:', error);
                alert('Simulation error: ' + error.message);
            } finally {
                this.simulating = false;
                this.simulationProgress = 100;
            }
        },

        updateDraftBoardWithPick(pick) {
            // Find the cell in the draft board table and update it
            const cellId = `roster-cell-${pick.team_id}-${pick.roster_position}`;
            const cell = document.getElementById(cellId);

            if (cell) {
                // Create the player info HTML
                cell.innerHTML = `
                    <div class="flex flex-col animate-pulse-once">
                        <span class="font-semibold text-gray-900">${pick.player}</span>
                        <span class="text-xs text-gray-500">${pick.mlb_team} - ${pick.positions}</span>
                    </div>
                `;
                // Add highlight animation
                cell.classList.add('bg-green-100');
                setTimeout(() => {
                    cell.classList.remove('bg-green-100');
                }, 2000);
            }
        },

        updateRecentPicksWithPick(pick) {
            const container = document.getElementById('recent-picks-list');
            if (!container) return;

            // Remove "no picks" message if present
            const noPicksMsg = document.getElementById('no-picks-message');
            if (noPicksMsg) noPicksMsg.remove();

            // Create new pick element
            const pickElement = document.createElement('div');
            pickElement.className = 'flex justify-between items-center p-3 bg-green-100 rounded pick-item animate-pulse-once';
            pickElement.dataset.pickId = pick.overall_pick;
            pickElement.innerHTML = `
                <div>
                    <span class="font-semibold">Pick #${pick.overall_pick}:</span>
                    <span class="ml-2">${pick.player}</span>
                    <span class="text-gray-600 ml-2">(${pick.positions})</span>
                </div>
                <div class="text-sm text-gray-600">
                    ${pick.team}
                </div>
            `;

            // Insert at the top
            container.insertBefore(pickElement, container.firstChild);

            // Fade the background after animation
            setTimeout(() => {
                pickElement.classList.remove('bg-green-100');
                pickElement.classList.add('bg-gray-50');
            }, 2000);

            // Keep only the most recent 15 picks visible
            const allPicks = container.querySelectorAll('.pick-item');
            if (allPicks.length > 15) {
                allPicks[allPicks.length - 1].remove();
            }
        },

        removePlayerFromAvailable(playerId) {
            // Remove player from the availablePlayersData array so they don't show in rankings
            const index = availablePlayersData.findIndex(p => p.id === playerId);
            if (index !== -1) {
                availablePlayersData.splice(index, 1);
            }
        }
    }
}

function playerRankings() {
    return {
        filterPosition: 'ALL',
        sortBy: 'points',
        sortDir: 'desc',
        allPlayers: availablePlayersData,

        init() {
            // Watch for changes to sortBy and adjust sortDir accordingly
            this.$watch('sortBy', (value) => {
                if (value === 'adp') {
                    this.sortDir = 'asc'; // ADP: lower is better
                } else if (value === 'points') {
                    this.sortDir = 'desc'; // Points: higher is better
                } else if (value === 'name') {
                    this.sortDir = 'asc'; // Name: alphabetical
                }
            });
        },

        get filteredPlayers() {
            let players = this.allPlayers;

            // Apply position filter
            if (this.filterPosition === 'BATTERS') {
                players = players.filter(p => !p.is_pitcher);
            } else if (this.filterPosition === 'PITCHERS') {
                players = players.filter(p => p.is_pitcher);
            } else if (this.filterPosition !== 'ALL') {
                players = players.filter(p => {
                    const positions = p.positions ? p.positions.split(',').map(pos => pos.trim()) : [];
                    return positions.some(pos => pos.includes(this.filterPosition));
                });
            }

            // When sorting by ADP, filter out players without ADP
            if (this.sortBy === 'adp') {
                players = players.filter(p => p.adp !== null && p.adp !== undefined);
            }

            // Apply sorting
            return players.sort((a, b) => {
                let aVal, bVal;

                if (this.sortBy === 'points') {
                    aVal = a.points || 0;
                    bVal = b.points || 0;
                } else if (this.sortBy === 'adp') {
                    aVal = a.adp || 0;
                    bVal = b.adp || 0;
                } else if (this.sortBy === 'name') {
                    aVal = a.name || '';
                    bVal = b.name || '';
                    return this.sortDir === 'asc'
                        ? aVal.localeCompare(bVal)
                        : bVal.localeCompare(aVal);
                }

                return this.sortDir === 'asc' ? aVal - bVal : bVal - aVal;
            });
        },

        async quickDraft(player) {
            if (!confirm(`Draft ${player.name}?\n\nThe system will automatically assign them to the first available eligible position.`)) {
                return;
            }

            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('player_id', player.id);

            try {
                const response = await fetch('{{ route('drafts.pick', $draft) }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.position_filled) {
                        alert(`✓ Drafted ${player.name} to position ${data.position_filled}!`);
                    }
                    window.location.reload();
                } else {
                    let errorMessage = 'Unknown error';
                    try {
                        const data = await response.json();
                        errorMessage = data.error || data.message || 'Unknown error';
                    } catch (e) {
                        errorMessage = 'Server error';
                    }
                    alert('Failed to make pick: ' + errorMessage);
                }
            } catch (error) {
                console.error('Error making pick:', error);
                alert('Failed to make pick: ' + error.message);
            }
        }
    }
}
</script>

<style>
/* Select2 Tailwind-style customization */
.select2-container--default .select2-selection--single {
    height: 42px;
    padding: 6px 12px;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    background-color: white;
}

.select2-container--default .select2-selection--single:focus,
.select2-container--default.select2-container--focus .select2-selection--single {
    border-color: #3b82f6;
    outline: none;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 28px;
    color: #1f2937;
}

.select2-container--default .select2-selection--single .select2-selection__placeholder {
    color: #9ca3af;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 40px;
}

.select2-dropdown {
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #3b82f6;
}

.select2-container--default .select2-results__option[aria-selected=true] {
    background-color: #dbeafe;
    color: #1e40af;
}

.select2-player-result {
    padding: 8px 0;
}

.select2-search--dropdown .select2-search__field {
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    padding: 8px 12px;
}

.select2-search--dropdown .select2-search__field:focus {
    border-color: #3b82f6;
    outline: none;
}

/* Custom badges in results */
.select2-player-result .badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.select2-player-result .badge-pitcher {
    background-color: #dbeafe;
    color: #1e40af;
}

.select2-player-result .badge-batter {
    background-color: #d1fae5;
    color: #065f46;
}
</style>
@endpush
@endsection

