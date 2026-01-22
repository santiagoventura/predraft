<?php $__env->startSection('title', $league->name); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold"><?php echo e($league->name); ?></h1>
        <div class="space-x-2">
            <a href="<?php echo e(route('rankings.index', $league)); ?>"
               class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                📊 Rankings
            </a>
            <a href="<?php echo e(route('leagues.scoring.index', $league)); ?>"
               class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
                ⚙️ Scoring
            </a>
            <a href="<?php echo e(route('drafts.create', $league)); ?>"
               class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                Start New Draft
            </a>
            <a href="<?php echo e(route('leagues.edit', $league)); ?>"
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
                <dd class="font-semibold"><?php echo e($league->num_teams); ?></dd>
            </div>
            <div>
                <dt class="text-gray-600">Scoring</dt>
                <dd class="font-semibold"><?php echo e(ucfirst(str_replace('_', ' ', $league->scoring_format))); ?></dd>
            </div>
            <div>
                <dt class="text-gray-600">Roster Spots</dt>
                <dd class="font-semibold"><?php echo e($league->total_roster_spots); ?></dd>
            </div>
        </dl>
    </div>

    <div class="bg-white rounded-lg shadow p-6 md:col-span-2">
        <h3 class="text-lg font-bold mb-4">Roster Configuration</h3>
        <div class="grid grid-cols-4 gap-3">
            <?php $__currentLoopData = $league->positions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $position): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="text-center p-2 bg-gray-100 rounded">
                    <div class="font-bold"><?php echo e($position->position_code); ?></div>
                    <div class="text-sm text-gray-600"><?php echo e($position->slot_count); ?>x</div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h3 class="text-lg font-bold mb-4">Teams</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <?php $__currentLoopData = $league->teams; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $team): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="p-3 bg-gray-50 rounded">
                <div class="font-semibold"><?php echo e($team->name); ?></div>
                <div class="text-sm text-gray-600">Pick #<?php echo e($team->draft_slot); ?></div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>

<?php if($league->drafts->isNotEmpty()): ?>
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold mb-4">Drafts</h3>
        <div class="space-y-2">
            <?php $__currentLoopData = $league->drafts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $draft): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <div>
                        <div class="font-semibold"><?php echo e($draft->name); ?></div>
                        <div class="text-sm text-gray-600">
                            Status: <span class="font-medium"><?php echo e(ucfirst($draft->status)); ?></span>
                        </div>
                    </div>
                    <a href="<?php echo e(route('drafts.show', $draft)); ?>" 
                       class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        View Draft
                    </a>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
<?php endif; ?>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/leagues/show.blade.php ENDPATH**/ ?>