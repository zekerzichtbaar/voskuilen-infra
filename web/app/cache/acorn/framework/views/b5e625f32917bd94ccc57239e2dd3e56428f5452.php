<footer class="bg-black text-white">
  <div class="container py-12 md:py-20">
    <div class="flex flex-col md:flex-row justify-between items-center gap-10">
      <?php $__currentLoopData = get_field('footer_content', 'option'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="flex flex-col max-w-sm">
          <?php if($item['title'] && in_array('title', $item['content_items'])): ?>
            <?php echo $item['title']; ?>

          <?php endif; ?>
          <?php if($item['button'] && in_array('button', $item['content_items'])): ?>
            <div class="mt-6 md:mt-10">
              <?php if (isset($component)) { $__componentOriginalc254754b9d5db91d5165876f9d051922ca0066f4 = $component; } ?>
<?php $component = $__env->getContainer()->make(Illuminate\View\AnonymousComponent::class, ['view' => 'components.button','data' => ['type' => ''.e($item['button']['type']).'','href' => ''.e($item['button']['link']['url']).'','target' => ''.e($item['button']['link']['target']).'']]); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php $component->withAttributes(['type' => ''.e($item['button']['type']).'','href' => ''.e($item['button']['link']['url']).'','target' => ''.e($item['button']['link']['target']).'']); ?><?php echo $item['button']['link']['title']; ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc254754b9d5db91d5165876f9d051922ca0066f4)): ?>
<?php $component = $__componentOriginalc254754b9d5db91d5165876f9d051922ca0066f4; ?>
<?php unset($__componentOriginalc254754b9d5db91d5165876f9d051922ca0066f4); ?>
<?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
  </div>
  <div class="container"><div class="w-full h-0.5 mx-auto bg-white/10"></div></div>
  <div class="container py-12 md:py-20">
    <div class="flex flex-col md:flex-row justify-between">
      <div class="flex flex-col md:flex-row gap-10 md:gap-28">
        <div class="flex flex-col gap-4 md:gap-8">
          <?php if(has_nav_menu('footer1_navigation')): ?>
            <?php echo wp_nav_menu(['theme_location' => 'footer1_navigation', 'menu_class' => 'text-white font-normal flex flex-col gap-2 md:gap-4', 'echo' => false]); ?>

          <?php endif; ?>
        </div>
        <div class="flex flex-col gap-4 md:gap-8">
          <?php if(has_nav_menu('footer2_navigation')): ?>
            <?php echo wp_nav_menu(['theme_location' => 'footer2_navigation', 'menu_class' => 'text-white font-normal flex flex-col gap-2 md:gap-4', 'echo' => false]); ?>

          <?php endif; ?>
        </div>
        <div class="flex flex-col gap-4 md:gap-8">
          <?php if(has_nav_menu('footer3_navigation')): ?>
            <?php echo wp_nav_menu(['theme_location' => 'footer3_navigation', 'menu_class' => 'text-white font-normal flex flex-col gap-2 md:gap-4', 'echo' => false]); ?>

          <?php endif; ?>
        </div>
      </div>
      <div class="flex flex-col gap-6 mt-10 md:mt-0">
        <?php if(get_field('social_title', 'option')): ?>
          <h5><?php echo e(get_field('social_title', 'option')); ?></h5>
        <?php endif; ?>
        <div class="flex gap-4">
          <?php if(get_field('social_icons', 'option')): ?>
            <?php $__currentLoopData = get_field('social_icons', 'option'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <a href="<?php echo $item['url']; ?>" target="blank">
                <div class="flex w-full h-full justify-center items-center border-2 border-white/10 p-3">
                  <?php if($item['icon'] == 'svg'): ?>
                    <?php echo $item['svg']; ?>

                  <?php else: ?>
                    <?php echo wp_get_attachment_image( $item['image']['ID'], isset($size), "", ["class" => "w-full h-full"] ); ?>

                  <?php endif; ?>
                </div>
              </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <div class="container"><div class="w-full h-0.5 mx-auto bg-white/10"></div></div>
    <div class="container py-8 md:py-12">
      <div class="flex justify-center md:justify-between items-center">
        <div class="hidden md:block">
          <?php if(has_nav_menu('footer_policy_navigation')): ?>
            <?php echo wp_nav_menu(['theme_location' => 'footer_policy_navigation', 'menu_class' => 'text-white font-normal flex gap-4 md:gap-6', 'echo' => false]); ?>

          <?php endif; ?>
        </div>
        <div>
          <p class="text-white/30">Realisatie door <a href="https://zekerzichtbaar.nl/" target="blank">Zeker Zichtbaar</a></p>
        </div>
      </div>
    </div>
</footer>
<?php /**PATH /Users/thijsbrons/Sites/voskuilen-infra/web/app/themes/voskuilen-infra/resources/views/sections/footer.blade.php ENDPATH**/ ?>