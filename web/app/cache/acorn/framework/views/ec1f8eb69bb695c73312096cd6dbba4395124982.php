<header class="banner fixed top-0 z-40 w-full">
  <div class="max-w-[95vw] md:max-w-[80vw] mx-auto text-white">
    <div class="flex justify-between items-center md:items-end">
      <a class="brand" href="<?php echo e(home_url('/')); ?>">
        <img class="w-20 md:w-auto h-auto" src="<?php echo e(asset('images/logo.svg')); ?>">
      </a>

      <?php if(has_nav_menu('primary_navigation')): ?>
        <nav class="nav-primary hidden md:flex" aria-label="<?php echo e(wp_get_nav_menu_name('primary_navigation')); ?>">
          <?php echo wp_nav_menu(['theme_location' => 'primary_navigation', 'menu_class' => 'nav flex gap-16 font-semibold', 'echo' => false]); ?>

        </nav>
      <?php endif; ?>

      <div id="menu" class="hamburger flex justify-center items-center gap-4 border border-white px-6 py-3 md:px-8 md:py-6 cursor-pointer transition duration-300 ease-in-out">
        <span class="font-black uppercase">Menu</span>
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
<?php /**PATH /Users/thijsbrons/Sites/voskuilen-infra/web/app/themes/voskuilen-infra/resources/views/sections/header.blade.php ENDPATH**/ ?>