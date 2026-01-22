<?php $__env->startSection('content'); ?>
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold">Edit Scoring Configuration</h1>
            <p class="text-gray-600 mt-1"><?php echo e($league->name); ?></p>
        </div>
        <a href="<?php echo e(route('leagues.scoring.index', $league)); ?>" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
            Cancel
        </a>
    </div>
</div>

<!-- Preset Selection -->
<div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
    <h2 class="text-xl font-bold mb-3">Quick Setup: Apply a Preset</h2>
    <p class="text-gray-700 mb-4">Start with a standard scoring configuration and customize it below.</p>
    
    <form action="<?php echo e(route('leagues.scoring.preset', $league)); ?>" method="POST" class="flex gap-4 items-end">
        <?php echo csrf_field(); ?>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Select Preset</label>
            <select name="preset" class="border rounded px-3 py-2 w-64">
                <option value="yahoo">Yahoo Fantasy (Default)</option>
                <option value="espn">ESPN Fantasy</option>
                <option value="cbs">CBS Sports</option>
            </select>
        </div>
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
            Apply Preset
        </button>
    </form>
</div>

<form action="<?php echo e(route('leagues.scoring.update', $league)); ?>" method="POST" x-data="scoringForm()">
    <?php echo csrf_field(); ?>
    <?php echo method_field('PUT'); ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Batter Scoring Categories -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold mb-4">Batter Scoring</h2>
            
            <div class="space-y-3 mb-4">
                <template x-for="(category, index) in batterCategories" :key="index">
                    <div class="flex gap-2 items-center border-b pb-2">
                        <div class="flex-1">
                            <select x-model="category.stat_code" 
                                    @change="updateBatterStatName(index)"
                                    :name="'batter_categories[' + index + '][stat_code]'"
                                    class="border rounded px-2 py-1 w-full text-sm">
                                <option value="">Select Stat...</option>
                                <?php $__currentLoopData = $batterStats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($code); ?>"><?php echo e($code); ?> - <?php echo e($name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <input type="hidden" x-model="category.stat_name" 
                                   :name="'batter_categories[' + index + '][stat_name]'">
                        </div>
                        <div class="w-32">
                            <input type="number" step="0.01" x-model="category.points_per_unit"
                                   :name="'batter_categories[' + index + '][points_per_unit]'"
                                   placeholder="Points"
                                   class="border rounded px-2 py-1 w-full text-sm">
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" x-model="category.is_active"
                                   :name="'batter_categories[' + index + '][is_active]'"
                                   value="1"
                                   class="mr-1">
                            <label class="text-xs">Active</label>
                        </div>
                        <button type="button" @click="removeBatterCategory(index)"
                                class="text-red-600 hover:text-red-800 text-sm">
                            Remove
                        </button>
                    </div>
                </template>
            </div>
            
            <button type="button" @click="addBatterCategory()"
                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-sm">
                + Add Batter Category
            </button>
        </div>

        <!-- Pitcher Scoring Categories -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold mb-4">Pitcher Scoring</h2>
            
            <div class="space-y-3 mb-4">
                <template x-for="(category, index) in pitcherCategories" :key="index">
                    <div class="flex gap-2 items-center border-b pb-2">
                        <div class="flex-1">
                            <select x-model="category.stat_code"
                                    @change="updatePitcherStatName(index)"
                                    :name="'pitcher_categories[' + index + '][stat_code]'"
                                    class="border rounded px-2 py-1 w-full text-sm">
                                <option value="">Select Stat...</option>
                                <?php $__currentLoopData = $pitcherStats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($code); ?>"><?php echo e($code); ?> - <?php echo e($name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <input type="hidden" x-model="category.stat_name"
                                   :name="'pitcher_categories[' + index + '][stat_name]'">
                        </div>
                        <div class="w-32">
                            <input type="number" step="0.01" x-model="category.points_per_unit"
                                   :name="'pitcher_categories[' + index + '][points_per_unit]'"
                                   placeholder="Points"
                                   class="border rounded px-2 py-1 w-full text-sm">
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" x-model="category.is_active"
                                   :name="'pitcher_categories[' + index + '][is_active]'"
                                   value="1"
                                   class="mr-1">
                            <label class="text-xs">Active</label>
                        </div>
                        <button type="button" @click="removePitcherCategory(index)"
                                class="text-red-600 hover:text-red-800 text-sm">
                            Remove
                        </button>
                    </div>
                </template>
            </div>
            
            <button type="button" @click="addPitcherCategory()"
                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-sm">
                + Add Pitcher Category
            </button>
        </div>
    </div>

    <div class="flex justify-end gap-4">
        <a href="<?php echo e(route('leagues.scoring.index', $league)); ?>" 
           class="bg-gray-500 text-white px-6 py-3 rounded hover:bg-gray-600">
            Cancel
        </a>
        <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700">
            Save Scoring Configuration
        </button>
    </div>
</form>

<script>
function scoringForm() {
    const batterCategoriesData = <?php echo json_encode($league->batterScoringCategories->values(), 15, 512) ?>;
    const pitcherCategoriesData = <?php echo json_encode($league->pitcherScoringCategories->values(), 15, 512) ?>;

    return {
        batterCategories: batterCategoriesData.map(cat => ({
            stat_code: cat.stat_code,
            stat_name: cat.stat_name,
            points_per_unit: cat.points_per_unit,
            is_active: cat.is_active
        })),
        pitcherCategories: pitcherCategoriesData.map(cat => ({
            stat_code: cat.stat_code,
            stat_name: cat.stat_name,
            points_per_unit: cat.points_per_unit,
            is_active: cat.is_active
        })),
        batterStats: <?php echo json_encode($batterStats, 15, 512) ?>,
        pitcherStats: <?php echo json_encode($pitcherStats, 15, 512) ?>,

        addBatterCategory() {
            this.batterCategories.push({
                stat_code: '',
                stat_name: '',
                points_per_unit: 0,
                is_active: true
            });
        },

        removeBatterCategory(index) {
            this.batterCategories.splice(index, 1);
        },

        updateBatterStatName(index) {
            const code = this.batterCategories[index].stat_code;
            this.batterCategories[index].stat_name = this.batterStats[code] || '';
        },

        addPitcherCategory() {
            this.pitcherCategories.push({
                stat_code: '',
                stat_name: '',
                points_per_unit: 0,
                is_active: true
            });
        },

        removePitcherCategory(index) {
            this.pitcherCategories.splice(index, 1);
        },

        updatePitcherStatName(index) {
            const code = this.pitcherCategories[index].stat_code;
            this.pitcherCategories[index].stat_name = this.pitcherStats[code] || '';
        }
    }
}
</script>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/leagues/scoring/edit.blade.php ENDPATH**/ ?>