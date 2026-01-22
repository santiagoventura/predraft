@extends('layouts.app')

@section('title', 'Drafts')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold">All Drafts</h1>
</div>

@if($drafts->isEmpty())
    <div class="bg-white rounded-lg shadow p-8 text-center">
        <p class="text-gray-600 mb-4">No drafts yet. Create a league first!</p>
        <a href="{{ route('leagues.index') }}" class="bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700">
            View Leagues
        </a>
    </div>
@else
    <div class="bg-white rounded-lg shadow">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Draft Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">League</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($drafts as $draft)
                    <tr>
                        <td class="px-6 py-4">{{ $draft->name }}</td>
                        <td class="px-6 py-4">{{ $draft->league?->name ?? 'League Deleted' }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded
                                @if($draft->status === 'completed') bg-green-100 text-green-800
                                @elseif($draft->status === 'in_progress') bg-blue-100 text-blue-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($draft->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $draft->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-3">
                                <a href="{{ route('drafts.show', $draft) }}"
                                   class="text-blue-600 hover:text-blue-800">
                                    View
                                </a>
                                <form action="{{ route('drafts.destroy', $draft) }}"
                                      method="POST"
                                      onsubmit="return confirm('Are you sure you want to delete this draft?\n\nThis will permanently delete:\n- All draft picks\n- All team rosters\n- All draft data\n\nThis action cannot be undone!');"
                                      class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-red-600 hover:text-red-800">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection

