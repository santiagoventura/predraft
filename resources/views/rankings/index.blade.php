@extends('layouts.app')

@section('title', 'Player Rankings - ' . $league->name)

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold">📊 Player Rankings</h1>
            <p class="text-gray-600">{{ $league->name }} - Review stats, points, and scoring formulas</p>
        </div>
        <a href="{{ route('leagues.show', $league) }}"
           class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
            ← Back to League
        </a>
    </div>
</div>

<!-- Stats Summary -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-2xl font-bold text-blue-600">{{ $totalPlayers }}</div>
        <div class="text-gray-600">Total Players</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-2xl font-bold text-green-600">{{ $totalBatters }}</div>
        <div class="text-gray-600">Batters</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-2xl font-bold text-purple-600">{{ $totalPitchers }}</div>
        <div class="text-gray-600">Pitchers</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-2xl font-bold text-orange-600">{{ $batterCategories->count() + $pitcherCategories->count() }}</div>
        <div class="text-gray-600">Scoring Categories</div>
    </div>
</div>

<!-- Scoring Formulas Section -->
<div class="bg-white rounded-lg shadow p-6 mb-6" x-data="{ showFormulas: false }">
    <div class="flex justify-between items-center cursor-pointer" @click="showFormulas = !showFormulas">
        <h2 class="text-xl font-bold">📐 Scoring Formulas</h2>
        <button class="text-blue-600 hover:text-blue-800">
            <span x-show="!showFormulas">Show ▼</span>
            <span x-show="showFormulas">Hide ▲</span>
        </button>
    </div>

    <div x-show="showFormulas" x-transition class="mt-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Batter Scoring -->
            <div>
                <h3 class="font-bold text-lg mb-3 text-green-700">⚾ Batter Scoring</h3>
                <table class="w-full text-sm">
                    <thead class="bg-green-50">
                        <tr>
                            <th class="text-left p-2">Category</th>
                            <th class="text-right p-2">Points/Unit</th>
                            <th class="text-left p-2">Formula</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($batterCategories as $cat)
                        <tr class="border-b">
                            <td class="p-2 font-medium">{{ $cat->stat_name }}</td>
                            <td class="p-2 text-right font-mono {{ $cat->points_per_unit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $cat->points_per_unit >= 0 ? '+' : '' }}{{ number_format($cat->points_per_unit, 2) }}
                            </td>
                            <td class="p-2 text-gray-600 font-mono text-xs">
                                {{ $cat->stat_code }} × {{ $cat->points_per_unit }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pitcher Scoring -->
            <div>
                <h3 class="font-bold text-lg mb-3 text-purple-700">🎯 Pitcher Scoring</h3>
                <table class="w-full text-sm">
                    <thead class="bg-purple-50">
                        <tr>
                            <th class="text-left p-2">Category</th>
                            <th class="text-right p-2">Points/Unit</th>
                            <th class="text-left p-2">Formula</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pitcherCategories as $cat)
                        <tr class="border-b">
                            <td class="p-2 font-medium">{{ $cat->stat_name }}</td>
                            <td class="p-2 text-right font-mono {{ $cat->points_per_unit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $cat->points_per_unit >= 0 ? '+' : '' }}{{ number_format($cat->points_per_unit, 2) }}
                            </td>
                            <td class="p-2 text-gray-600 font-mono text-xs">
                                {{ $cat->stat_code }} × {{ $cat->points_per_unit }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4 p-3 bg-gray-50 rounded text-sm text-gray-600">
            <strong>How it works:</strong> Each player's projected stats are multiplied by the points per unit,
            then summed to get total projected points. For example: 40 HR × 10.4 pts = 416 points from home runs.
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" action="{{ route('rankings.index', $league) }}" class="flex flex-wrap gap-4 items-end">
        <!-- Player Type Filter -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Player Type</label>
            <select name="type" class="border rounded px-3 py-2">
                <option value="all" {{ $playerType === 'all' ? 'selected' : '' }}>All Players</option>
                <option value="batter" {{ $playerType === 'batter' ? 'selected' : '' }}>Batters Only</option>
                <option value="pitcher" {{ $playerType === 'pitcher' ? 'selected' : '' }}>Pitchers Only</option>
            </select>
        </div>

        <!-- Position Filter -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
            <select name="position" class="border rounded px-3 py-2">
                @foreach($positions as $key => $label)
                    <option value="{{ $key }}" {{ $position === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <!-- Search -->
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
            <input type="text" name="search" value="{{ $search }}"
                   placeholder="Player name or team..."
                   class="border rounded px-3 py-2 w-full">
        </div>

        <!-- Per Page -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Per Page</label>
            <select name="per_page" class="border rounded px-3 py-2">
                @foreach([25, 50, 100, 200] as $num)
                    <option value="{{ $num }}" {{ $perPage == $num ? 'selected' : '' }}>{{ $num }}</option>
                @endforeach
            </select>
        </div>

        <!-- Sort By -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
            <select name="sort" class="border rounded px-3 py-2">
                <option value="points" {{ $sortBy === 'points' ? 'selected' : '' }}>Total Points</option>
                <option value="adp" {{ $sortBy === 'adp' ? 'selected' : '' }}>ADP</option>
            </select>
        </div>

        <!-- Sort Direction -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Order</label>
            <select name="dir" class="border rounded px-3 py-2">
                <option value="desc" {{ $sortDir === 'desc' ? 'selected' : '' }}>{{ $sortBy === 'adp' ? 'Low → High' : 'High → Low' }}</option>
                <option value="asc" {{ $sortDir === 'asc' ? 'selected' : '' }}>{{ $sortBy === 'adp' ? 'High → Low' : 'Low → High' }}</option>
            </select>
        </div>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            🔍 Filter
        </button>
        <a href="{{ route('rankings.index', $league) }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">
            Reset
        </a>
    </form>
</div>

<!-- Player Rankings Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="text-left p-3 sticky left-0 bg-gray-100">#</th>
                    <th class="text-left p-3 sticky left-0 bg-gray-100">Player</th>
                    <th class="text-left p-3">Pos</th>
                    <th class="text-left p-3">Team</th>
                    <th class="text-right p-3">
                        <a href="{{ route('rankings.index', array_merge(request()->query(), ['league' => $league->id, 'sort' => 'adp', 'dir' => ($sortBy === 'adp' && $sortDir === 'asc') ? 'desc' : 'asc'])) }}"
                           class="hover:text-orange-800 {{ $sortBy === 'adp' ? 'text-orange-600 font-bold' : 'text-orange-500' }}">
                            ADP {!! $sortBy === 'adp' ? ($sortDir === 'asc' ? '▲' : '▼') : '' !!}
                        </a>
                    </th>
                    <th class="text-right p-3">
                        <a href="{{ route('rankings.index', array_merge(request()->query(), ['league' => $league->id, 'sort' => 'points', 'dir' => ($sortBy === 'points' && $sortDir === 'desc') ? 'asc' : 'desc'])) }}"
                           class="hover:text-blue-800 {{ $sortBy === 'points' ? 'text-blue-700 font-bold' : 'text-blue-600' }}">
                            Total Pts {!! $sortBy === 'points' ? ($sortDir === 'desc' ? '▼' : '▲') : '' !!}
                        </a>
                    </th>
                    <th class="text-left p-3">Category Breakdown</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rankings as $index => $score)
                    @php
                        $player = $score->player;
                        $projection = $player->latestProjection;
                        $breakdown = $score->category_breakdown ?? [];
                        $rank = ($rankings->currentPage() - 1) * $rankings->perPage() + $index + 1;
                        $adpRanking = $player->adpRanking;
                        $adp = $adpRanking ? $adpRanking->adp : null;
                    @endphp
                    <tr class="border-b hover:bg-gray-50 {{ $player->is_pitcher ? 'bg-purple-50/30' : '' }}">
                        <td class="p-3 font-bold text-gray-500">{{ $rank }}</td>
                        <td class="p-3">
                            <div class="font-semibold">{{ $player->name }}</div>
                        </td>
                        <td class="p-3">
                            <span class="px-2 py-1 rounded text-xs font-medium
                                {{ $player->is_pitcher ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800' }}">
                                {{ $player->positions }}
                            </span>
                        </td>
                        <td class="p-3 text-gray-600">{{ $player->mlb_team }}</td>
                        <td class="p-3 text-right">
                            @if($adp)
                                <span class="font-semibold text-orange-600">{{ number_format($adp, 1) }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="p-3 text-right">
                            <span class="font-bold text-lg text-blue-600">{{ number_format($score->total_points, 1) }}</span>
                        </td>
                        <td class="p-3">
                            <div x-data="{ expanded: false }">
                                <button @click="expanded = !expanded" class="text-xs text-blue-600 hover:underline">
                                    <span x-show="!expanded">Show breakdown ▼</span>
                                    <span x-show="expanded">Hide ▲</span>
                                </button>
                                <div x-show="expanded" x-transition class="mt-2 text-xs">
                                    @if($player->is_pitcher && isset($breakdown['pitcher']))
                                        <div class="grid grid-cols-3 gap-1">
                                            @foreach($breakdown['pitcher'] as $code => $data)
                                                <div class="bg-purple-50 p-1 rounded">
                                                    <span class="font-medium">{{ $code }}:</span>
                                                    <span>{{ is_array($data) ? ($data['value'] ?? 0) : $data }}</span>
                                                    <span class="text-purple-600">
                                                        ({{ is_array($data) ? number_format($data['points'] ?? 0, 1) : 0 }} pts)
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @elseif(!$player->is_pitcher && isset($breakdown['batter']))
                                        <div class="grid grid-cols-3 gap-1">
                                            @foreach($breakdown['batter'] as $code => $data)
                                                <div class="bg-green-50 p-1 rounded">
                                                    <span class="font-medium">{{ $code }}:</span>
                                                    <span>{{ is_array($data) ? ($data['value'] ?? 0) : $data }}</span>
                                                    <span class="text-green-600">
                                                        ({{ is_array($data) ? number_format($data['points'] ?? 0, 1) : 0 }} pts)
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-gray-400">No breakdown available</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-gray-500">
                            No players found. Make sure to calculate scores from the league scoring page.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="p-4 border-t">
        {{ $rankings->links() }}
    </div>
</div>
@endsection
