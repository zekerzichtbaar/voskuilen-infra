<header class="banner fixed top-0 z-40 w-full">
  <div class="max-w-[95vw] md:max-w-[80vw] mx-auto text-white">
    <div class="flex justify-between items-center pt-[50px]">
      <a class="brand -mt-[50px]" href="{{ home_url('/') }}">
        <img class="w-20 md:w-auto h-auto" src="{{ asset('images/logo.svg') }}">
      </a>

      @if (has_nav_menu('primary_navigation'))
        <nav class="nav-primary hidden md:flex" aria-label="{{ wp_get_nav_menu_name('primary_navigation') }}">
          {!! wp_nav_menu(['theme_location' => 'primary_navigation', 'menu_class' => 'nav flex gap-16 font-semibold', 'echo' => false]) !!}
        </nav>
      @endif

      <div class="flex justify-center items-center gap-4 border border-white px-6 py-3 md:px-8 md:py-6">
        <span class="font-black uppercase h-5">Menu</span>
        <div class="flex flex-col gap-1">
          <div class="w-6 h-[3px] bg-white"></div>
          <div class="w-6 h-[3px] bg-white"></div>
          <div class="w-6 h-[3px] bg-white"></div>
        </div>
      </div>
    </div>
  </div>
</header>
