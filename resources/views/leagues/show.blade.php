@extends('layouts.app')

@section('title', $league->name)

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold">{{ $league->name }}</h1>
        <div class="space-x-2">
            <a href="{{ route('rankings.index', $league) }}"
               class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                📊 Rankings
            </a>
            <a href="{{ route('leagues.scoring.index', $league) }}"
               class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
                ⚙️ Scoring
            </a>
            <a href="{{ route('drafts.create', $league) }}"
               class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                Start New Draft
            </a>
            <a href="{{ route('leagues.edit', $league) }}"
               class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                Edit League
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold mb-2">League Info</h3>
        <dl class="space-y-2">
            <div>
                <dt class="text-gray-600">Teams</dt>
                <dd class="font-semibold">{{ $league->num_teams }}</dd>
            </div>
            <div>
                <dt class="text-gray-600">Scoring</dt>
                <dd class="font-semibold">{{ ucfirst(str_replace('_', ' ', $league->scoring_format)) }}</dd>
            </div>
            <div>
                <dt class="text-gray-600">Roster Spots</dt>
                <dd class="font-semibold">{{ $league->total_roster_spots }}</dd>
            </div>
        </dl>
    </div>

    <div class="bg-white rounded-lg shadow p-6 md:col-span-2">
        <h3 class="text-lg font-bold mb-4">Roster Configuration</h3>
        <div class="grid grid-cols-4 gap-3">
            @foreach($league->positions as $position)
                <div class="text-center p-2 bg-gray-100 rounded">
                    <div class="font-bold">{{ $position->position_code }}</div>
                    <div class="text-sm text-gray-600">{{ $position->slot_count }}x</div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h3 class="text-lg font-bold mb-4">Teams</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @foreach($league->teams as $team)
            <div class="p-3 bg-gray-50 rounded">
                <div class="font-semibold">{{ $team->name }}</div>
                <div class="text-sm text-gray-600">Pick #{{ $team->draft_slot }}</div>
            </div>
        @endforeach
    </div>
</div>

@if($league->drafts->isNotEmpty())
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold mb-4">Drafts</h3>
        <div class="space-y-2">
            @foreach($league->drafts as $draft)
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <div>
                        <div class="font-semibold">{{ $draft->name }}</div>
                        <div class="text-sm text-gray-600">
                            Status: <span class="font-medium">{{ ucfirst($draft->status) }}</span>
                        </div>
                    </div>
                    <a href="{{ route('drafts.show', $draft) }}" 
                       class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        View Draft
                    </a>
                </div>
            @endforeach
        </div>
    </div>
@endif
@endsection

