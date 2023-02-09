<footer class="bg-black text-white">
  <div class="container py-12 md:py-20">
    <div class="flex flex-col md:flex-row justify-between items-center gap-10">
      @foreach(get_field('footer_content', 'option') as $item)
        <div class="flex flex-col max-w-sm">
          @if ($item['title'] && in_array('title', $item['content_items']))
            {!! $item['title'] !!}
          @endif
          @if ($item['button'] && in_array('button', $item['content_items']))
            <div class="mt-6 md:mt-10">
              <x-button type="{{ $item['button']['type'] }}" href="{{ $item['button']['link']['url'] }}" target="{{ $item['button']['link']['target'] }}">{!! $item['button']['link']['title'] !!}</x-button>
            </div>
          @endif
        </div>
      @endforeach
    </div>
  </div>
  <div class="container"><div class="w-full h-0.5 mx-auto bg-white/10"></div></div>
  <div class="container py-12 md:py-20">
    <div class="flex flex-col md:flex-row justify-between">
      <div class="flex flex-col md:flex-row gap-10 md:gap-28">
        <div class="flex flex-col gap-4 md:gap-8">
          @if (has_nav_menu('footer1_navigation'))
            {!! wp_nav_menu(['theme_location' => 'footer1_navigation', 'menu_class' => 'text-white font-normal flex flex-col gap-2 md:gap-4', 'echo' => false]) !!}
          @endif
        </div>
        <div class="flex flex-col gap-4 md:gap-8">
          @if (has_nav_menu('footer2_navigation'))
            {!! wp_nav_menu(['theme_location' => 'footer2_navigation', 'menu_class' => 'text-white font-normal flex flex-col gap-2 md:gap-4', 'echo' => false]) !!}
          @endif
        </div>
        <div class="flex flex-col gap-4 md:gap-8">
          @if (has_nav_menu('footer3_navigation'))
            {!! wp_nav_menu(['theme_location' => 'footer3_navigation', 'menu_class' => 'text-white font-normal flex flex-col gap-2 md:gap-4', 'echo' => false]) !!}
          @endif
        </div>
      </div>
      <div class="flex flex-col gap-6 mt-10 md:mt-0">
        @if(get_field('social_title', 'option'))
          <h5>{{ get_field('social_title', 'option') }}</h5>
        @endif
        <div class="flex gap-4">
          @if(get_field('social_icons', 'option'))
            @foreach(get_field('social_icons', 'option') as $item)
              <a href="{!! $item['url'] !!}" target="blank">
                <div class="flex w-full h-full justify-center items-center border-2 border-white/10 hover:border-white/80 transition duration-300 ease-in-out p-3">
                  @if($item['icon'] == 'svg')
                    {!! $item['svg'] !!}
                  @else
                    {!! wp_get_attachment_image( $item['image']['ID'], isset($size), "", ["class" => "w-full h-full"] ) !!}
                  @endif
                </div>
              </a>
            @endforeach
          @endif
        </div>
      </div>
    </div>
  </div>
  <div class="container"><div class="w-full h-0.5 mx-auto bg-white/10"></div></div>
    <div class="container py-8 md:py-12">
      <div class="flex justify-center md:justify-between items-center">
        <div class="hidden md:block">
          @if (has_nav_menu('footer_policy_navigation'))
            {!! wp_nav_menu(['theme_location' => 'footer_policy_navigation', 'menu_class' => 'text-white font-normal flex gap-4 md:gap-6', 'echo' => false]) !!}
          @endif
        </div>
        <div>
          <p class="text-white/30">Realisatie door <a class="hover:text-white/80 transition duration-300 ease-in-out" href="https://zekerzichtbaar.nl/" target="blank">Zeker Zichtbaar</a></p>
        </div>
      </div>
    </div>
</footer>
