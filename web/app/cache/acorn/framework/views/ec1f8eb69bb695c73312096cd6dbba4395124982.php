<header class="banner md:px-0 fixed py-[15px] md:py-12 z-30 w-full border-x-[15px] md:border-x-[30px] border-transparent">
  <div class="container mx-auto text-white">
    <div class="flex justify-between items-center">
      <a class="brand md:-mt-[50px]" href="<?php echo e(home_url('/')); ?>">
        <img class="w-20 md:w-auto h-auto" src="<?php echo e(asset('images/logo.svg')); ?>">
      </a>

      <?php if(has_nav_menu('primary_navigation')): ?>
        <nav class="nav-primary hidden lg:flex" aria-label="<?php echo e(wp_get_nav_menu_name('primary_navigation')); ?>">
          <?php echo wp_nav_menu(['theme_location' => 'primary_navigation', 'menu_class' => 'nav flex gap-16 font-semibold', 'echo' => false]); ?>

        </nav>
      <?php endif; ?>

      <div id="menuBtn" class="hamburger flex justify-center items-center gap-4 border border-white px-6 py-3 md:px-8 md:py-6 cursor-pointer transition duration-300 ease-in-out">
        <span id="menuBtnText" class="font-black uppercase pt-1">menu</span>
        <div id="hamburger" class="flex">
          <div class="icon-left">
            <span class="block w-4 h-[3px] transition duration-300 ease-in-out bg-white"></span>
            <span class="block w-4 h-[3px] mt-[6px] transition duration-300 ease-in-out bg-white"></span>
            <span class="block w-4 h-[3px] mt-[6px] transition duration-300 ease-in-out bg-white"></span>
          </div>
          <div class="icon-right -ml-1.5">
            <span class="block w-4 h-[3px] transition duration-300 ease-in-out bg-white"></span>
            <span class="block w-4 h-[3px] mt-[6px] transition duration-300 ease-in-out bg-white"></span>
            <span class="block w-4 h-[3px] mt-[6px] transition duration-300 ease-in-out bg-white"></span>
          </div>
        </div>
      </div>
    </div>
  </div>
</header>
<div id="menuScreen" class="fixed -translate-y-full opacity-0 w-full z-20 h-screen bg-black transition duration-300 ease-in-out">
  <div class="container">
    <div class="flex flex-col sm:flex-row gap-12 md:gap-24 2xl:gap-40 justify-center lg:justify-start sm:items-center h-screen">
      <div class="highlight-nav flex flex-col">
        <span class="font-normal text-lg text-white/80 mb-4 md:mb-8">Wat we doen</span>
        <?php if(has_nav_menu('highlighted_navigation')): ?>
          <?php echo wp_nav_menu(['theme_location' => 'highlighted_navigation', 'menu_class' => 'font-bold flex flex-col gap-2 md:gap-4', 'echo' => false]); ?>

        <?php endif; ?>
      </div>
      <div class="flex flex-col">
        <?php if(has_nav_menu('secondary_navigation')): ?>
          <?php echo wp_nav_menu(['theme_location' => 'secondary_navigation', 'menu_class' => 'text-white/60 font-normal flex flex-col gap-2 md:gap-4', 'echo' => false]); ?>

        <?php endif; ?>
      </div>
    </div>
    <div class="hidden lg:block absolute right-0 top-0 w-[30%] bg-primary h-full">
      <div class="absolute inset-0 z-30 bg-gradient-to-r from-black to-transparent"></div> 
        <?php
          $items = wp_get_nav_menu_items('Highlighted');
          foreach((array) $items as $key => $item) {
            $image = get_field('image', $item->ID);
            $menuId = "menu-item-$item->ID";
            echo '<div class="menu-image absolute inset-0 w-full h-full transition-all duration-500 ease-in-out opacity-0 z-20" data-id="' . $menuId . '">' . wp_get_attachment_image( $image, isset($size), "", ["class" => "menu-image absolute inset-0 object-cover w-full h-full"]) . '</div>';
          }
        ?>
      <img class="menu-image absolute inset-0 w-full h-full z-10 object-cover" src="<?= \Roots\asset('images/menu-placeholder.png'); ?>">
      <div class="absolute flex flex-col justify-center h-full">
        <h3 class="text-white font-bold z-30"><?php echo get_field('title', 'option'); ?></h3>
        <span class="text-white/70 z-30"><?php echo get_field('menu_content', 'option'); ?></span>
        <?php if (isset($component)) { $__componentOriginalc254754b9d5db91d5165876f9d051922ca0066f4 = $component; } ?>
<?php $component = $__env->getContainer()->make(Illuminate\View\AnonymousComponent::class, ['view' => 'components.button','data' => ['class' => 'z-30 mt-6 md:mt-10','type' => 'primary','href' => ''.e(home_url('/contact')).'']]); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php $component->withAttributes(['class' => 'z-30 mt-6 md:mt-10','type' => 'primary','href' => ''.e(home_url('/contact')).'']); ?>Neem contact op <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc254754b9d5db91d5165876f9d051922ca0066f4)): ?>
<?php $component = $__componentOriginalc254754b9d5db91d5165876f9d051922ca0066f4; ?>
<?php unset($__componentOriginalc254754b9d5db91d5165876f9d051922ca0066f4); ?>
<?php endif; ?>
      </div>
    </div>
  </div>
</div><?php /**PATH /Users/thijsbrons/Sites/voskuilen-infra/web/app/themes/voskuilen-infra/resources/views/sections/header.blade.php ENDPATH**/ ?>