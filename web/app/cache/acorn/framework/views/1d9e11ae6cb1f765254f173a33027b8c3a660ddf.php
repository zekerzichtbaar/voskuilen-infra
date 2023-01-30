<section class="relative border-[30px] border-white py-0">
    <?php if($type == "header"): ?>
        <div class="text-white" style="height: 80vh;">
            <?php if($background = "bg_image"): ?>
                <?php if($bg_image): ?><?php echo wp_get_attachment_image( $bg_image['ID'], isset($size), "", ["class" => "w-full h-full absolute inset-0 object-cover"] ); ?><?php endif; ?>
                <div class="absolute inset-0 bg-gradient-to-b from-black/70 via-transparent to-black/70"></div>
            <?php else: ?>
                <?php if($bg_image): ?><video autoplay muted src="<?php echo e($bg_video); ?>" class="w-full h-full absolute inset-0 object-cover"</><?php endif; ?>
                <div class="absolute inset-0 bg-gradient-to-b from-black/70 via-transparent to-black/70"></div>
            <?php endif; ?>
            <div class="container mx-auto h-full flex">
                <h1 class="relative mt-auto mb-28"><?php echo $title; ?></h1>
                <div class="absolute bottom-0 translate-y-1/2 text-white flex flex-wrap gap-6">
                    <?php if($buttons): ?>
                        <?php $__currentLoopData = $buttons; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $button): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php if (isset($component)) { $__componentOriginalc254754b9d5db91d5165876f9d051922ca0066f4 = $component; } ?>
<?php $component = $__env->getContainer()->make(Illuminate\View\AnonymousComponent::class, ['view' => 'components.button','data' => ['type' => ''.e($button['type']).'','href' => ''.e($button['link']['url']).'','target' => ''.e($button['link']['target']).'']]); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php $component->withAttributes(['type' => ''.e($button['type']).'','href' => ''.e($button['link']['url']).'','target' => ''.e($button['link']['target']).'']); ?><?php echo $button['link']['title']; ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc254754b9d5db91d5165876f9d051922ca0066f4)): ?>
<?php $component = $__componentOriginalc254754b9d5db91d5165876f9d051922ca0066f4; ?>
<?php unset($__componentOriginalc254754b9d5db91d5165876f9d051922ca0066f4); ?>
<?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php elseif($type == "subheader"): ?>
        <div class="text-white" style="height: 50vh;">
            <?php if($bg_image): ?><?php echo wp_get_attachment_image( $bg_image['ID'], isset($size), "", ["class" => "w-full h-full absolute inset-0 object-cover"] ); ?><?php endif; ?>
            <div class="absolute inset-0 bg-gradient-to-b from-black/70 via-transparent to-black/70"></div>
            <div class="container mx-auto h-full flex items-center">
                <h1 class="relative"><?php echo $title; ?></h1>
            </div>
        </div>
    <?php endif; ?>
</section><?php /**PATH /Users/thijsbrons/Sites/voskuilen-infra/web/app/themes/voskuilen-infra/resources/views/blocks/header.blade.php ENDPATH**/ ?>