<?php $__env->startSection('title', 'Create League'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold mb-6">Create New League</h1>

    <form action="<?php echo e(route('leagues.store')); ?>" method="POST" class="bg-white rounded-lg shadow p-6">
        <?php echo csrf_field(); ?>

        <div class="mb-6">
            <label class="block text-gray-700 font-bold mb-2">League Name</label>
            <input type="text" name="name" value="<?php echo e(old('name')); ?>" required
                   class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-gray-700 font-bold mb-2">Number of Teams</label>
                <input type="number" name="num_teams" value="<?php echo e(old('num_teams', 12)); ?>" min="2" max="20" required
                       class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-gray-700 font-bold mb-2">Scoring Format</label>
                <select name="scoring_format" required
                        class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="roto">Rotisserie</option>
                    <option value="h2h_categories">H2H Categories</option>
                    <option value="h2h_points">H2H Points</option>
                </select>
            </div>
        </div>

        <div class="mb-6">
            <h3 class="text-xl font-bold mb-4">Roster Positions</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php $__currentLoopData = $defaultPositions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $position): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1"><?php echo e($position['position_code']); ?></label>
                        <input type="hidden" name="positions[<?php echo e($index); ?>][position_code]" value="<?php echo e($position['position_code']); ?>">
                        <input type="number" 
                               name="positions[<?php echo e($index); ?>][slot_count]" 
                               value="<?php echo e(old("positions.{$index}.slot_count", $position['slot_count'])); ?>" 
                               min="0" max="20"
                               class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <p class="text-sm text-gray-600 mt-2">Set to 0 to exclude a position</p>
        </div>

        <div class="flex space-x-4">
            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700">
                Create League
            </button>
            <a href="<?php echo e(route('leagues.index')); ?>" class="bg-gray-300 text-gray-700 px-6 py-3 rounded hover:bg-gray-400">
                Cancel
            </a>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/leagues/create.blade.php ENDPATH**/ ?>