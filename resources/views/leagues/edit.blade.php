@extends('layouts.app')

@section('title', 'Edit League')

@section('content')
<div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold mb-6">Edit League: {{ $league->name }}</h1>

    <form action="{{ route('leagues.update', $league) }}" method="POST" class="bg-white rounded-lg shadow p-6">
        @csrf
        @method('PUT')

        <div class="mb-6">
            <label class="block text-gray-700 font-bold mb-2">League Name</label>
            <input type="text" name="name" value="{{ old('name', $league->name) }}" required
                   class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-gray-700 font-bold mb-2">Number of Teams</label>
                <input type="number" name="num_teams" value="{{ old('num_teams', $league->num_teams) }}" min="2" max="20" required
                       class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-gray-700 font-bold mb-2">Scoring Format</label>
                <select name="scoring_format" required
                        class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="roto" {{ $league->scoring_format === 'roto' ? 'selected' : '' }}>Rotisserie</option>
                    <option value="h2h_categories" {{ $league->scoring_format === 'h2h_categories' ? 'selected' : '' }}>H2H Categories</option>
                    <option value="h2h_points" {{ $league->scoring_format === 'h2h_points' ? 'selected' : '' }}>H2H Points</option>
                </select>
            </div>
        </div>

        <div class="mb-6">
            <h3 class="text-xl font-bold mb-4">Roster Positions</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($league->positions as $position)
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1">{{ $position->position_code }}</label>
                        <input type="hidden" name="positions[{{ $loop->index }}][position_code]" value="{{ $position->position_code }}">
                        <input type="number" 
                               name="positions[{{ $loop->index }}][slot_count]" 
                               value="{{ old("positions.{$loop->index}.slot_count", $position->slot_count) }}" 
                               min="0" max="20"
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                @endforeach
            </div>
            <p class="text-sm text-gray-600 mt-2">Set to 0 to exclude a position</p>
        </div>

        <div class="flex space-x-4">
            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700">
                Update League
            </button>
            <a href="{{ route('leagues.show', $league) }}" class="bg-gray-300 text-gray-700 px-6 py-3 rounded hover:bg-gray-400">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection

