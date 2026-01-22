<?php $__env->startSection('title', 'Leagues'); ?>

<?php $__env->startSection('content'); ?>
<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold">My Leagues</h1>
    <div class="flex gap-2">
        <a href="<?php echo e(route('admin.player-data.index')); ?>" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
            📊 Player Data
        </a>
        <a href="<?php echo e(route('leagues.create')); ?>" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Create New League
        </a>
    </div>
</div>

<?php if($leagues->isEmpty()): ?>
    <div class="bg-white rounded-lg shadow p-8 text-center">
        <p class="text-gray-600 mb-4">No leagues yet. Create your first league to get started!</p>
        <a href="<?php echo e(route('leagues.create')); ?>" class="bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700">
            Create League
        </a>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php $__currentLoopData = $leagues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $league): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="bg-white rounded-lg shadow hover:shadow-lg transition">
                <div class="p-6">
                    <h2 class="text-xl font-bold mb-2"><?php echo e($league->name); ?></h2>
                    <div class="text-gray-600 space-y-1 mb-4">
                        <p><?php echo e($league->num_teams); ?> Teams</p>
                        <p><?php echo e(ucfirst(str_replace('_', ' ', $league->scoring_format))); ?></p>
                        <p><?php echo e($league->total_roster_spots); ?> Roster Spots</p>
                    </div>
                    <div class="flex space-x-2">
                        <a href="<?php echo e(route('leagues.show', $league)); ?>" 
                           class="flex-1 bg-blue-600 text-white text-center px-4 py-2 rounded hover:bg-blue-700">
                            View
                        </a>
                        <a href="<?php echo e(route('drafts.create', $league)); ?>" 
                           class="flex-1 bg-green-600 text-white text-center px-4 py-2 rounded hover:bg-green-700">
                            New Draft
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
<?php endif; ?>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/leagues/index.blade.php ENDPATH**/ ?>