<?php $__env->startSection('title', 'Drafts'); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-6">
    <h1 class="text-3xl font-bold">All Drafts</h1>
</div>

<?php if($drafts->isEmpty()): ?>
    <div class="bg-white rounded-lg shadow p-8 text-center">
        <p class="text-gray-600 mb-4">No drafts yet. Create a league first!</p>
        <a href="<?php echo e(route('leagues.index')); ?>" class="bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700">
            View Leagues
        </a>
    </div>
<?php else: ?>
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
                <?php $__currentLoopData = $drafts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $draft): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td class="px-6 py-4"><?php echo e($draft->name); ?></td>
                        <td class="px-6 py-4"><?php echo e($draft->league?->name ?? 'League Deleted'); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded
                                <?php if($draft->status === 'completed'): ?> bg-green-100 text-green-800
                                <?php elseif($draft->status === 'in_progress'): ?> bg-blue-100 text-blue-800
                                <?php else: ?> bg-gray-100 text-gray-800
                                <?php endif; ?>">
                                <?php echo e(ucfirst($draft->status)); ?>

                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <?php echo e($draft->created_at->format('M d, Y')); ?>

                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-3">
                                <a href="<?php echo e(route('drafts.show', $draft)); ?>"
                                   class="text-blue-600 hover:text-blue-800">
                                    View
                                </a>
                                <form action="<?php echo e(route('drafts.destroy', $draft)); ?>"
                                      method="POST"
                                      onsubmit="return confirm('Are you sure you want to delete this draft?\n\nThis will permanently delete:\n- All draft picks\n- All team rosters\n- All draft data\n\nThis action cannot be undone!');"
                                      class="inline">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit"
                                            class="text-red-600 hover:text-red-800">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/drafts/index.blade.php ENDPATH**/ ?>