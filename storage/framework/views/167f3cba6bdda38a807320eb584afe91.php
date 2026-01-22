<?php $__env->startSection('title', 'Player Rankings - ' . $league->name); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold">📊 Player Rankings</h1>
            <p class="text-gray-600"><?php echo e($league->name); ?> - Review stats, points, and scoring formulas</p>
        </div>
        <a href="<?php echo e(route('leagues.show', $league)); ?>"
           class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
            ← Back to League
        </a>
    </div>
</div>

<!-- Stats Summary -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-2xl font-bold text-blue-600"><?php echo e($totalPlayers); ?></div>
        <div class="text-gray-600">Total Players</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-2xl font-bold text-green-600"><?php echo e($totalBatters); ?></div>
        <div class="text-gray-600">Batters</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-2xl font-bold text-purple-600"><?php echo e($totalPitchers); ?></div>
        <div class="text-gray-600">Pitchers</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-2xl font-bold text-orange-600"><?php echo e($batterCategories->count() + $pitcherCategories->count()); ?></div>
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
                        <?php $__currentLoopData = $batterCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr class="border-b">
                            <td class="p-2 font-medium"><?php echo e($cat->stat_name); ?></td>
                            <td class="p-2 text-right font-mono <?php echo e($cat->points_per_unit >= 0 ? 'text-green-600' : 'text-red-600'); ?>">
                                <?php echo e($cat->points_per_unit >= 0 ? '+' : ''); ?><?php echo e(number_format($cat->points_per_unit, 2)); ?>

                            </td>
                            <td class="p-2 text-gray-600 font-mono text-xs">
                                <?php echo e($cat->stat_code); ?> × <?php echo e($cat->points_per_unit); ?>

                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                        <?php $__currentLoopData = $pitcherCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr class="border-b">
                            <td class="p-2 font-medium"><?php echo e($cat->stat_name); ?></td>
                            <td class="p-2 text-right font-mono <?php echo e($cat->points_per_unit >= 0 ? 'text-green-600' : 'text-red-600'); ?>">
                                <?php echo e($cat->points_per_unit >= 0 ? '+' : ''); ?><?php echo e(number_format($cat->points_per_unit, 2)); ?>

                            </td>
                            <td class="p-2 text-gray-600 font-mono text-xs">
                                <?php echo e($cat->stat_code); ?> × <?php echo e($cat->points_per_unit); ?>

                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
    <form method="GET" action="<?php echo e(route('rankings.index', $league)); ?>" class="flex flex-wrap gap-4 items-end">
        <!-- Player Type Filter -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Player Type</label>
            <select name="type" class="border rounded px-3 py-2">
                <option value="all" <?php echo e($playerType === 'all' ? 'selected' : ''); ?>>All Players</option>
                <option value="batter" <?php echo e($playerType === 'batter' ? 'selected' : ''); ?>>Batters Only</option>
                <option value="pitcher" <?php echo e($playerType === 'pitcher' ? 'selected' : ''); ?>>Pitchers Only</option>
            </select>
        </div>

        <!-- Position Filter -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
            <select name="position" class="border rounded px-3 py-2">
                <?php $__currentLoopData = $positions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($key); ?>" <?php echo e($position === $key ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>

        <!-- Search -->
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
            <input type="text" name="search" value="<?php echo e($search); ?>"
                   placeholder="Player name or team..."
                   class="border rounded px-3 py-2 w-full">
        </div>

        <!-- Per Page -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Per Page</label>
            <select name="per_page" class="border rounded px-3 py-2">
                <?php $__currentLoopData = [25, 50, 100, 200]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $num): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($num); ?>" <?php echo e($perPage == $num ? 'selected' : ''); ?>><?php echo e($num); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>

        <!-- Sort By -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
            <select name="sort" class="border rounded px-3 py-2">
                <option value="points" <?php echo e($sortBy === 'points' ? 'selected' : ''); ?>>Total Points</option>
                <option value="adp" <?php echo e($sortBy === 'adp' ? 'selected' : ''); ?>>ADP</option>
            </select>
        </div>

        <!-- Sort Direction -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Order</label>
            <select name="dir" class="border rounded px-3 py-2">
                <option value="desc" <?php echo e($sortDir === 'desc' ? 'selected' : ''); ?>><?php echo e($sortBy === 'adp' ? 'Low → High' : 'High → Low'); ?></option>
                <option value="asc" <?php echo e($sortDir === 'asc' ? 'selected' : ''); ?>><?php echo e($sortBy === 'adp' ? 'High → Low' : 'Low → High'); ?></option>
            </select>
        </div>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            🔍 Filter
        </button>
        <a href="<?php echo e(route('rankings.index', $league)); ?>" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">
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
                        <a href="<?php echo e(route('rankings.index', array_merge(request()->query(), ['league' => $league->id, 'sort' => 'adp', 'dir' => ($sortBy === 'adp' && $sortDir === 'asc') ? 'desc' : 'asc']))); ?>"
                           class="hover:text-orange-800 <?php echo e($sortBy === 'adp' ? 'text-orange-600 font-bold' : 'text-orange-500'); ?>">
                            ADP <?php echo $sortBy === 'adp' ? ($sortDir === 'asc' ? '▲' : '▼') : ''; ?>

                        </a>
                    </th>
                    <th class="text-right p-3">
                        <a href="<?php echo e(route('rankings.index', array_merge(request()->query(), ['league' => $league->id, 'sort' => 'points', 'dir' => ($sortBy === 'points' && $sortDir === 'desc') ? 'asc' : 'desc']))); ?>"
                           class="hover:text-blue-800 <?php echo e($sortBy === 'points' ? 'text-blue-700 font-bold' : 'text-blue-600'); ?>">
                            Total Pts <?php echo $sortBy === 'points' ? ($sortDir === 'desc' ? '▼' : '▲') : ''; ?>

                        </a>
                    </th>
                    <th class="text-left p-3">Category Breakdown</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $rankings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $score): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $player = $score->player;
                        $projection = $player->latestProjection;
                        $breakdown = $score->category_breakdown ?? [];
                        $rank = ($rankings->currentPage() - 1) * $rankings->perPage() + $index + 1;
                        $adpRanking = $player->adpRanking;
                        $adp = $adpRanking ? $adpRanking->adp : null;
                    ?>
                    <tr class="border-b hover:bg-gray-50 <?php echo e($player->is_pitcher ? 'bg-purple-50/30' : ''); ?>">
                        <td class="p-3 font-bold text-gray-500"><?php echo e($rank); ?></td>
                        <td class="p-3">
                            <div class="font-semibold"><?php echo e($player->name); ?></div>
                        </td>
                        <td class="p-3">
                            <span class="px-2 py-1 rounded text-xs font-medium
                                <?php echo e($player->is_pitcher ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800'); ?>">
                                <?php echo e($player->positions); ?>

                            </span>
                        </td>
                        <td class="p-3 text-gray-600"><?php echo e($player->mlb_team); ?></td>
                        <td class="p-3 text-right">
                            <?php if($adp): ?>
                                <span class="font-semibold text-orange-600"><?php echo e(number_format($adp, 1)); ?></span>
                            <?php else: ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-3 text-right">
                            <span class="font-bold text-lg text-blue-600"><?php echo e(number_format($score->total_points, 1)); ?></span>
                        </td>
                        <td class="p-3">
                            <div x-data="{ expanded: false }">
                                <button @click="expanded = !expanded" class="text-xs text-blue-600 hover:underline">
                                    <span x-show="!expanded">Show breakdown ▼</span>
                                    <span x-show="expanded">Hide ▲</span>
                                </button>
                                <div x-show="expanded" x-transition class="mt-2 text-xs">
                                    <?php if($player->is_pitcher && isset($breakdown['pitcher'])): ?>
                                        <div class="grid grid-cols-3 gap-1">
                                            <?php $__currentLoopData = $breakdown['pitcher']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code => $data): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <div class="bg-purple-50 p-1 rounded">
                                                    <span class="font-medium"><?php echo e($code); ?>:</span>
                                                    <span><?php echo e(is_array($data) ? ($data['value'] ?? 0) : $data); ?></span>
                                                    <span class="text-purple-600">
                                                        (<?php echo e(is_array($data) ? number_format($data['points'] ?? 0, 1) : 0); ?> pts)
                                                    </span>
                                                </div>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>
                                    <?php elseif(!$player->is_pitcher && isset($breakdown['batter'])): ?>
                                        <div class="grid grid-cols-3 gap-1">
                                            <?php $__currentLoopData = $breakdown['batter']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code => $data): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <div class="bg-green-50 p-1 rounded">
                                                    <span class="font-medium"><?php echo e($code); ?>:</span>
                                                    <span><?php echo e(is_array($data) ? ($data['value'] ?? 0) : $data); ?></span>
                                                    <span class="text-green-600">
                                                        (<?php echo e(is_array($data) ? number_format($data['points'] ?? 0, 1) : 0); ?> pts)
                                                    </span>
                                                </div>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-gray-400">No breakdown available</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7" class="p-8 text-center text-gray-500">
                            No players found. Make sure to calculate scores from the league scoring page.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="p-4 border-t">
        <?php echo e($rankings->links()); ?>

    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/rankings/index.blade.php ENDPATH**/ ?>