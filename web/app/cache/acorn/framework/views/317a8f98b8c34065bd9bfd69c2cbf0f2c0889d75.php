<section class="container">
    <div class="grid_template">
        <?php
            $position = [
                'col-span-5 aspect-square',
                'col-span-7',
                'col-span-6 aspect-square',
                'col-span-6 aspect-square'
            ];
        ?>
        <?php $__currentLoopData = $grid_items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $grid_item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="bg-white <?php echo e($position[$loop->index]); ?>">
                <?php if($grid_item['acf_fc_layout'] == 'project'): ?>
                    <?php ($projects = get_post_or_latest()); ?>
                    <?php while($projects->have_posts()): ?> <?php ($projects->the_post()); ?>
                        <?php echo $__env->first(['partials.content-' . get_post_type()], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                    <?php endwhile; ?>
                    <?php (wp_reset_postdata()); ?>
                <?php elseif($grid_item['acf_fc_layout'] == 'page_links'): ?>
                    <div class="p-20">
                        <h2 class="text-3xl font-bold"><?php echo e($grid_item['title']); ?></h2>
                        <ul>
                            <?php $__currentLoopData = $grid_item['selected_pages']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li class="py-2 mt-2 pl-8 border-b"><a href="<?php echo e(get_permalink($post->ID)); ?>"><?php echo e($page->post_title); ?></a></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <pre>
        
    </div>
</section><?php /**PATH /Users/thijsbrons/Sites/voskuilen-infra/web/app/themes/voskuilen-infra/resources/views/blocks/grid.blade.php ENDPATH**/ ?>