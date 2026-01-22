<?php $__env->startSection('content'); ?>
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold">Scoring Configuration</h1>
            <p class="text-gray-600 mt-1"><?php echo e($league->name); ?></p>
        </div>
        <div class="space-x-2">
            <a href="<?php echo e(route('leagues.show', $league)); ?>" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                Back to League
            </a>
            <a href="<?php echo e(route('leagues.scoring.edit', $league)); ?>" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Edit Scoring
            </a>
        </div>
    </div>
</div>

<?php if(session('success')): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <?php echo e(session('success')); ?>

    </div>
<?php endif; ?>

<?php if($errors->any()): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <ul>
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li><?php echo e($error); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Batter Scoring Categories -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold mb-4">Batter Scoring</h2>
        
        <?php if($league->batterScoringCategories->isEmpty()): ?>
            <p class="text-gray-600 mb-4">No batter scoring categories configured.</p>
            <a href="<?php echo e(route('leagues.scoring.edit', $league)); ?>" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Configure Scoring
            </a>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2 text-left">Stat</th>
                            <th class="px-4 py-2 text-right">Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $league->batterScoringCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr class="border-t">
                                <td class="px-4 py-2">
                                    <span class="font-semibold"><?php echo e($category->stat_code); ?></span>
                                    <span class="text-gray-600 text-sm ml-2"><?php echo e($category->stat_name); ?></span>
                                </td>
                                <td class="px-4 py-2 text-right font-mono">
                                    <span class="<?php echo e($category->points_per_unit >= 0 ? 'text-green-600' : 'text-red-600'); ?>">
                                        <?php echo e($category->points_per_unit > 0 ? '+' : ''); ?><?php echo e(number_format($category->points_per_unit, 2)); ?>

                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pitcher Scoring Categories -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold mb-4">Pitcher Scoring</h2>
        
        <?php if($league->pitcherScoringCategories->isEmpty()): ?>
            <p class="text-gray-600 mb-4">No pitcher scoring categories configured.</p>
            <a href="<?php echo e(route('leagues.scoring.edit', $league)); ?>" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Configure Scoring
            </a>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2 text-left">Stat</th>
                            <th class="px-4 py-2 text-right">Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $league->pitcherScoringCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr class="border-t">
                                <td class="px-4 py-2">
                                    <span class="font-semibold"><?php echo e($category->stat_code); ?></span>
                                    <span class="text-gray-600 text-sm ml-2"><?php echo e($category->stat_name); ?></span>
                                </td>
                                <td class="px-4 py-2 text-right font-mono">
                                    <span class="<?php echo e($category->points_per_unit >= 0 ? 'text-green-600' : 'text-red-600'); ?>">
                                        <?php echo e($category->points_per_unit > 0 ? '+' : ''); ?><?php echo e(number_format($category->points_per_unit, 2)); ?>

                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Calculate Scores Section -->
<?php if(!$league->batterScoringCategories->isEmpty() || !$league->pitcherScoringCategories->isEmpty()): ?>
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-2xl font-bold mb-4">Calculate Player Scores</h2>
    <p class="text-gray-600 mb-4">
        Calculate projected fantasy points for all players based on your scoring configuration.
    </p>
    
    <form action="<?php echo e(route('leagues.scoring.calculate', $league)); ?>" method="POST" class="flex gap-4 items-end">
        <?php echo csrf_field(); ?>
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
<?php endif; ?>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/leagues/scoring/index.blade.php ENDPATH**/ ?>