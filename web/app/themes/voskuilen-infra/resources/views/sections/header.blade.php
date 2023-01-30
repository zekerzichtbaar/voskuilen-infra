<header class="banner fixed top-0 z-40 w-full border-x-[30px] border-white">
  <div class="container mx-auto text-white">
    <div class="flex justify-between items-center pt-[60px]">
      <a class="brand -mt-[60px]" href="{{ home_url('/') }}">
        <img class="w-20 md:w-auto h-auto" src="{{ asset('images/logo.svg') }}">
      </a>

      @if (has_nav_menu('primary_navigation'))
        <nav class="nav-primary hidden lg:flex" aria-label="{{ wp_get_nav_menu_name('primary_navigation') }}">
          {!! wp_nav_menu(['theme_location' => 'primary_navigation', 'menu_class' => 'nav flex gap-16 font-semibold', 'echo' => false]) !!}
        </nav>
      @endif

      <div id="menu" class="hamburger flex justify-center items-center gap-4 border border-white px-6 py-3 md:px-8 md:py-6 cursor-pointer transition duration-300 ease-in-out">
        <span class="font-black uppercase h-5">Menu</span>
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
