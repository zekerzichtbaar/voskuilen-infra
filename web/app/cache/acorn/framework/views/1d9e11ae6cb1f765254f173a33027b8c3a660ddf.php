<section class="relative border-[30px] border-white">
        <div class="<?php echo e($type = "header" ? "pt-52" : ""); ?>">
            <?php if($background = "bg_image"): ?>
                <?php if($bg_image): ?><?php echo wp_get_attachment_image( $bg_image['ID'], isset($size), "", ["class" => "w-full h-full absolute inset-0 object-cover"] ); ?><?php endif; ?>
                <div class="absolute inset-x-0 bg-gradient-to-t from-black/70 to-transparent h-1/2 bottom-0 z-10"></div>
                <div class="absolute inset-x-0 bg-gradient-to-b from-black/70 to-transparent h-1/2 top-0 z-10"></div>
            <?php else: ?>
                <?php if($bg_image): ?><video autoplay muted src="<?php echo e($bg_video); ?>" class="w-full h-full absolute inset-0 object-cover"</><?php endif; ?>
                <div class="absolute inset-x-0 bg-gradient-to-t from-black/60 to-transparent h-1/2 bottom-0 z-10"></div>
                <div class="absolute inset-x-0 bg-gradient-to-b from-black/60 to-transparent h-1/2 top-0 z-10"></div>
            <?php endif; ?>
        </div>
        <div class="z-10 -bottom-[10.2rem] pl-24 relative text-white">
            <h1><?php echo $title; ?></h1>
            <div class="mt-20 flex gap-4">
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
</section><?php /**PATH /Users/thijsbrons/Sites/voskuilen-infra/web/app/themes/voskuilen-infra/resources/views/blocks/header.blade.php ENDPATH**/ ?>