<header class="banner md:px-0 fixed py-[15px] md:py-12 z-30 w-full border-x-[15px] md:border-x-[30px] border-transparent transition-transform duration-300 bg-gradient-to-b from-black/50 to-transparent">
  <div class="container mx-auto text-white">
    <div class="flex justify-between items-center">
      <a class="brand md:-mt-[50px]" href="{{ home_url('/') }}">
        <img class="w-20 md:w-auto h-auto" src="{{ asset('images/logo.svg') }}">
      </a>

      @if (has_nav_menu('primary_navigation'))
        <nav class="nav-primary hidden lg:flex" aria-label="{{ wp_get_nav_menu_name('primary_navigation') }}">
          {!! wp_nav_menu(['theme_location' => 'primary_navigation', 'menu_class' => 'nav flex gap-16 font-semibold', 'echo' => false]) !!}
        </nav>
      @endif

      <div id="menuBtn" class="hamburger flex justify-center items-center gap-4 border-2 border-white px-4 md:px-8 py-2 md:py-5 cursor-pointer transition duration-300 ease-in-out">
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

      <div id="menu-highlighted-wrapper" class="ease-in-out duration-300 delay-[400ms] -translate-y-4 opacity-0 flex flex-col">
        <span class="font-normal text-lg text-white/80 mb-4 md:mb-8">Wat we doen</span>
        @if (has_nav_menu('highlighted_navigation'))
          {!! wp_nav_menu(['theme_location' => 'highlighted_navigation', 'menu_class' => 'delay-300 font-bold flex flex-col gap-2 md:gap-4', 'echo' => false]) !!}
        @endif
      </div>

      <div id="menu-secondary-wrapper" class="ease-in-out duration-300 delay-[500ms] -translate-y-4 opacity-0 flex flex-col">
        @if (has_nav_menu('secondary_navigation'))
          {!! wp_nav_menu(['theme_location' => 'secondary_navigation', 'menu_class' => 'text-white/60 font-normal flex flex-col gap-2 md:gap-4 text-lg', 'echo' => false]) !!}
        @endif
      </div>

    </div>
    <div class="hidden lg:block absolute right-0 top-0 w-[30%] bg-primary h-full">
      <div class="absolute inset-0 z-30 bg-gradient-to-r from-black to-transparent"></div> 
        @php
          $items = wp_get_nav_menu_items('Highlighted');
          foreach((array) $items as $key => $item) {
            $image = get_field('image', $item->ID);
            $menuId = "menu-item-$item->ID";
            echo '<div class="menu-image absolute inset-0 w-full h-full transition-all duration-500 ease-in-out opacity-0 z-20" data-id="' . $menuId . '">' . wp_get_attachment_image( $image, isset($size), "", ["class" => "menu-image absolute inset-0 object-cover w-full h-full"]) . '</div>';
          }
        @endphp
      <img class="menu-image absolute inset-0 w-full h-full z-10 object-cover" src="@asset('images/menu-placeholder.png')">
      
      <div id="menu-contact-info" class="absolute z-30 left-4 2xl:left-16 flex flex-col justify-center h-full ease-in-out duration-300 delay-[600ms] -translate-y-4 opacity-0">
        <h3 class="text-white font-bold z-30">{!! get_field('title', 'option') !!}</h3>
        <span class="text-white z-30">{!! get_field('menu_content', 'option') !!}</span>
        <x-button class="z-30 mt-6 md:mt-10" type="primary" href="{{ home_url('/contact') }}">Neem contact op</x-button>
      </div>
    </div>
  </div>
</div>