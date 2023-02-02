<section class="relative <?php echo e($pt); ?> <?php echo e($pb); ?>">
    <div class="container">
        <div class="flex flex-col gap-24">
            <div class="flex flex-col justify-center items-center <?php echo e($layout == "text-image" ? "order-1" : "order-2"); ?>">
                <div class="<?php echo e($image_layout == "two-column" ? "max-w-2xl" : "max-w-3xl"); ?>">
                    <?php if($title && in_array('title', $content_items)): ?>
                        <h3 class="mb-6"><?php echo e($title); ?></h3>
                    <?php endif; ?>
                    <?php if($content && in_array('content', $content_items)): ?>
                        <span class="prose <?php echo e($image_layout == "two-column" ? "content" : "text-lg leading-9"); ?>"><?php echo $content; ?></span>
                    <?php endif; ?>
                    <?php if($buttons && in_array('buttons', $content_items)): ?>
                        <div class="flex flex-wrap gap-6 mt-6 md:mt-10">
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
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php if($image_layout == "two-column"): ?>
                <div class="flex flex-col lg:flex-row justify-center items-center gap-12 lg:gap-4 <?php echo e($layout == "text-image" ? "order-2" : "order-1"); ?>">
                    <?php if($two_column_image_one): ?>
                        <div class="relative aspect-square bg-primary h-full w-full md:w-[40rem] lg:w-[30rem]">
                            <?php echo wp_get_attachment_image( $two_column_image_one['ID'], isset($size), "", ["class" => "w-full h-full absolute inset-0 object-cover object-center"] ); ?>

                        </div>
                    <?php endif; ?>
                    <?php if($two_column_image_two): ?>
                        <div class="relative aspect-video bg-primary h-[50rem] w-full md:w-[40rem] lg:w-[30rem]">
                            <?php echo wp_get_attachment_image( $two_column_image_two['ID'], isset($size), "", ["class" => "w-full h-full absolute inset-0 object-cover object-center"] ); ?>

                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="flex flex-col lg:flex-row justify-center items-center gap-12 lg:gap-4 <?php echo e($layout == "text-image" ? "order-2" : "order-1"); ?>">
                    <?php if($one_column_image): ?>
                        <div class="relative aspect-video bg-primary h-full w-full">
                            <?php echo wp_get_attachment_image( $one_column_image['ID'], isset($size), "", ["class" => "w-full h-full absolute inset-0 object-cover object-center"] ); ?>

                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section><?php /**PATH /Users/thijsbrons/Sites/voskuilen-infra/web/app/themes/voskuilen-infra/resources/views/blocks/text-image.blade.php ENDPATH**/ ?>