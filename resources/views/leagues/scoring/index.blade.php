@extends('layouts.app')

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold">Scoring Configuration</h1>
            <p class="text-gray-600 mt-1">{{ $league->name }}</p>
        </div>
        <div class="space-x-2">
            <a href="{{ route('leagues.show', $league) }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                Back to League
            </a>
            <a href="{{ route('leagues.scoring.edit', $league) }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Edit Scoring
            </a>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Batter Scoring Categories -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold mb-4">Batter Scoring</h2>
        
        @if($league->batterScoringCategories->isEmpty())
            <p class="text-gray-600 mb-4">No batter scoring categories configured.</p>
            <a href="{{ route('leagues.scoring.edit', $league) }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Configure Scoring
            </a>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2 text-left">Stat</th>
                            <th class="px-4 py-2 text-right">Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($league->batterScoringCategories as $category)
                            <tr class="border-t">
                                <td class="px-4 py-2">
                                    <span class="font-semibold">{{ $category->stat_code }}</span>
                                    <span class="text-gray-600 text-sm ml-2">{{ $category->stat_name }}</span>
                                </td>
                                <td class="px-4 py-2 text-right font-mono">
                                    <span class="{{ $category->points_per_unit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $category->points_per_unit > 0 ? '+' : '' }}{{ number_format($category->points_per_unit, 2) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Pitcher Scoring Categories -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold mb-4">Pitcher Scoring</h2>
        
        @if($league->pitcherScoringCategories->isEmpty())
            <p class="text-gray-600 mb-4">No pitcher scoring categories configured.</p>
            <a href="{{ route('leagues.scoring.edit', $league) }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Configure Scoring
            </a>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2 text-left">Stat</th>
                            <th class="px-4 py-2 text-right">Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($league->pitcherScoringCategories as $category)
                            <tr class="border-t">
                                <td class="px-4 py-2">
                                    <span class="font-semibold">{{ $category->stat_code }}</span>
                                    <span class="text-gray-600 text-sm ml-2">{{ $category->stat_name }}</span>
                                </td>
                                <td class="px-4 py-2 text-right font-mono">
                                    <span class="{{ $category->points_per_unit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $category->points_per_unit > 0 ? '+' : '' }}{{ number_format($category->points_per_unit, 2) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<!-- Calculate Scores Section -->
@if(!$league->batterScoringCategories->isEmpty() || !$league->pitcherScoringCategories->isEmpty())
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-2xl font-bold mb-4">Calculate Player Scores</h2>
    <p class="text-gray-600 mb-4">
        Calculate projected fantasy points for all players based on your scoring configuration.
    </p>
    
    <form action="{{ route('leagues.scoring.calculate', $league) }}" method="POST" class="flex gap-4 items-end">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Season</label>
            <input type="number" name="season" value="2025" min="2020" max="2030" 
                   class="border rounded px-3 py-2 w-32">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Projection Source</label>
            <select name="source" class="border rounded px-3 py-2">
                <option value="fantasypros">FantasyPros</option>
                <option value="steamer">Steamer</option>
                <option value="zips">ZiPS</option>
                <option value="custom">Custom</option>
            </select>
        </div>
        <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">
            Calculate Scores
        </button>
    </form>
</div>
@endif
@endsection

