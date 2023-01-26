<header class="banner">
  <div class="container text-white">
    <div class="flex justify-between items-center">
      <a class="brand" href="<?php echo e(home_url('/')); ?>">
        <img src="<?php echo e(asset('images/logo.svg')); ?>">
      </a>

      <?php if(has_nav_menu('primary_navigation')): ?>
        <nav class="nav-primary" aria-label="<?php echo e(wp_get_nav_menu_name('primary_navigation')); ?>">
          <?php echo wp_nav_menu(['theme_location' => 'primary_navigation', 'menu_class' => 'nav flex gap-16 font-semibold', 'echo' => false]); ?>

        </nav>
      <?php endif; ?>

      <div class="flex justify-center items-center gap-4 border border-white px-8 py-6">
        <span class="font-black uppercase">Menu</span>
        <div class="flex flex-col gap-1">
          <div class="w-6 h-[3px] bg-white"></div>
          <div class="w-6 h-[3px] bg-white"></div>
          <div class="w-6 h-[3px] bg-white"></div>
        </div>
      </div>
    </div>
  </div>
</header>
<?php /**PATH /Users/thijsbrons/Sites/voskuilen-infra/web/app/themes/voskuilen-infra/resources/views/sections/header.blade.php ENDPATH**/ ?>