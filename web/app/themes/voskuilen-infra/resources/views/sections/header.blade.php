<header class="banner">
  <div class="container text-white">
    <div class="flex justify-between items-center">
      <a class="brand" href="{{ home_url('/') }}">
        <img src="{{ asset('images/logo.svg') }}">
      </a>

      @if (has_nav_menu('primary_navigation'))
        <nav class="nav-primary" aria-label="{{ wp_get_nav_menu_name('primary_navigation') }}">
          {!! wp_nav_menu(['theme_location' => 'primary_navigation', 'menu_class' => 'nav flex gap-16 font-semibold', 'echo' => false]) !!}
        </nav>
      @endif

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
