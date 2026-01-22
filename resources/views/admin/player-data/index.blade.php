<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player Data Management - MLB Draft Helper</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8" x-data="playerDataManager()" x-init="init()">
        <div class="mb-6">
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold">📊 Player Data Management</h1>
                <a href="{{ route('leagues.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                    ← Back to Leagues
                </a>
            </div>
            <p class="text-gray-600 mt-2">Automatically update player projections, injuries, and calculate fantasy scores</p>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-2xl font-bold mb-4">📈 Current Data (2026 Season)</h2>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-3xl font-bold text-blue-600">{{ $stats['total_players'] }}</div>
                    <div class="text-sm text-gray-600">Total Players</div>
                </div>
                
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-3xl font-bold text-green-600">{{ $stats['projections_2026'] }}</div>
                    <div class="text-sm text-gray-600">2026 Projections</div>
                </div>
                
                <div class="bg-red-50 p-4 rounded-lg">
                    <div class="text-3xl font-bold text-red-600">{{ $stats['injuries_active'] }}</div>
                    <div class="text-sm text-gray-600">Active Injuries</div>
                </div>
                
                <div class="bg-purple-50 p-4 rounded-lg">
                    <div class="text-3xl font-bold text-purple-600">{{ $stats['scores_2026'] }}</div>
                    <div class="text-sm text-gray-600">Calculated Scores</div>
                </div>
            </div>

            @if($lastUpdated)
            <div class="mt-4 p-3 bg-gray-50 rounded border border-gray-200">
                <div class="text-sm text-gray-600">
                    🕐 Last updated: <span class="font-semibold">{{ $lastUpdated }}</span>
                </div>
            </div>
            @else
            <div class="mt-4 p-3 bg-yellow-50 rounded border border-yellow-200">
                <div class="text-sm text-yellow-700">
                    ⚠️ No update has been run yet
                </div>
            </div>
            @endif
        </div>

        <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg shadow-lg p-8 mb-6 text-white">
            <h2 class="text-3xl font-bold mb-4">🚀 Update All Data</h2>
            <p class="mb-6 text-blue-100">Click the button below to automatically:</p>
            <ul class="mb-6 space-y-2 text-blue-100">
                <li>✓ Fetch latest player projections from FantasyPros</li>
                <li>✓ Update injury information</li>
                <li>✓ Calculate fantasy scores for all leagues</li>
            </ul>

            <form action="{{ route('admin.player-data.start-update') }}" method="POST" x-show="!updating">
                @csrf
                <input type="hidden" name="season" value="2026">
                <button type="submit" class="bg-white text-blue-600 px-8 py-4 rounded-lg hover:bg-blue-50 font-bold text-lg shadow-lg transition">
                    🔄 Start Update
                </button>
            </form>

            <div x-show="updating" class="mt-4">
                <div class="bg-white bg-opacity-20 rounded-full h-8 overflow-hidden">
                    <div class="bg-white h-full transition-all duration-500 flex items-center justify-center text-blue-600 font-bold"
                         :style="'width: ' + progress.percentage + '%'"
                         x-text="progress.percentage + '%'">
                    </div>
                </div>
                <div class="mt-3 text-white font-semibold" x-text="progress.message"></div>
                <div class="mt-2 text-blue-100 text-sm">This may take 1-2 minutes. The page will auto-refresh when complete.</div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-xl font-bold mb-4">👥 Players</h3>
                <div class="space-y-2">
                    <div class="flex justify-between"><span>Batters:</span><span class="font-semibold">{{ $stats['batters'] }}</span></div>
                    <div class="flex justify-between"><span>Pitchers:</span><span class="font-semibold">{{ $stats['pitchers'] }}</span></div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-xl font-bold mb-4">🏆 Leagues</h3>
                <div class="space-y-2">
                    <div class="flex justify-between"><span>Total Leagues:</span><span class="font-semibold">{{ $stats['leagues'] }}</span></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function playerDataManager() {
            return {
                updating: false,
                progress: {
                    message: 'Ready to update',
                    percentage: 0
                },
                pollInterval: null,

                init() {
                    this.checkProgress();
                },

                checkProgress() {
                    fetch('{{ route("admin.player-data.progress") }}')
                        .then(r => r.json())
                        .then(data => {
                            if (data.progress && data.progress.percentage > 0 && data.progress.percentage < 100) {
                                this.progress = data.progress;
                                this.updating = true;
                                if (!this.pollInterval) {
                                    this.startPolling();
                                }
                            }
                        })
                        .catch(e => console.log('Progress check:', e));
                },

                startPolling() {
                    this.pollInterval = setInterval(() => {
                        fetch('{{ route("admin.player-data.progress") }}')
                            .then(r => r.json())
                            .then(data => {
                                if (data.progress) {
                                    this.progress = data.progress;
                                    this.updating = data.progress.percentage > 0 && data.progress.percentage < 100;
                                    
                                    if (data.progress.percentage >= 100) {
                                        clearInterval(this.pollInterval);
                                        setTimeout(() => window.location.reload(), 2000);
                                    }
                                }
                            })
                            .catch(e => console.log('Polling error:', e));
                    }, 2000);
                }
            }
        }
    </script>
</body>
</html>
