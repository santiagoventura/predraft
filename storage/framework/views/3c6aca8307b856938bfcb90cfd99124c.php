<?php $__env->startSection('title', 'Create Draft'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-2xl mx-auto">
    <h1 class="text-3xl font-bold mb-6">Create New Draft</h1>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-bold mb-2">League: <?php echo e($league->name); ?></h3>
        <p class="text-gray-600"><?php echo e($league->num_teams); ?> teams, <?php echo e($league->total_roster_spots); ?> roster spots</p>
    </div>

    <form action="<?php echo e(route('drafts.store', $league)); ?>" method="POST" class="bg-white rounded-lg shadow p-6">
        <?php echo csrf_field(); ?>

        <div class="mb-6">
            <label class="block text-gray-700 font-bold mb-2">Draft Name (Optional)</label>
            <input type="text" name="name" value="<?php echo e(old('name')); ?>"
                   placeholder="e.g., 2025 Mock Draft #1"
                   class="w-full px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            <p class="text-sm text-gray-600 mt-1">Leave blank for auto-generated name</p>
        </div>

        <div class="flex space-x-4">
            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700">
                Create Draft
            </button>
            <a href="<?php echo e(route('leagues.show', $league)); ?>" 
               class="bg-gray-300 text-gray-700 px-6 py-3 rounded hover:bg-gray-400">
                Cancel
            </a>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/drafts/create.blade.php ENDPATH**/ ?>