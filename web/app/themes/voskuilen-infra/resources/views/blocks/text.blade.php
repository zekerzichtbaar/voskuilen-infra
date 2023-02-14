<section class="relative {{ $pt }} {{ $pb }} bg-{{ $background }}">
    <div class="container flex flex-col lg:flex-row justify-center gap-12 md:gap-[8rem]">
        @if(in_array('menu', $content_items) && $menu_title && $menu_items)
            <div class="flex flex-col">
                <span class="text-2xl font-semibold">{{ $menu_title }}</span>
                <ul class="mt-2 text-lg">
                    @foreach ($menu_items as $menu_item)
                        <li class="py-2">
                            <a href="{{ get_permalink($menu_item->ID) }}" class="flex items-center group">
                                <svg class="h-4 mr-1.5 mb-1 -ml-1 duration-150 text-black group-hover:translate-x-1 group-hover:text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                                </svg>
                                {{ $menu_item->post_title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div>
                @if($title && in_array('title', $content_items))
                    <{{ $heading }}>{{ $title }}</{{ $heading }}>
                @endif
                <div class="text-lg prose max-w-none mt-6">
                    {!! $content !!}
                </div>
            </div>
        @else
            <div class="flex flex-col text-{{ $text_align }} {{ $width == 'small' ? 'max-w-3xl' : ''}}">
                @if($text_type == 'prose')

                    @if($title && in_array('title', $content_items))
                        <{{ $heading }} class="mb-6">{{ $title }}</{{ $heading }}>
                    @endif
                    <div class="prose-lg leading-9 max-w-none w-full">
                        {!! $content !!}
                    </div>

                @elseif($text_type == 'large')

                    @if($title && in_array('title', $content_items))
                        <{{ $heading }} class="mb-6">{{ $title }}</{{ $heading }}>
                    @endif
                    <div class="prose text-xl md:text-3xl prose-p:leading-[2.75rem] prose-strong:text-primary prose-strong:font-semibold">
                        {!! $content !!}
                    </div>

                @elseif($text_type == 'quote')

                    <div class="flex flex-col text-center text-4xl">
                        <div class="bg-primary text-white rounded-full aspect-square w-16 flex items-center justify-center mx-auto">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="18" viewBox="0 0 24 18"><path fill="currentColor" d="M26.5263158,22 L30,25.2 C27.8289474,26.6666711 25.5131641,28.8666667 24.9342105,31.4 C27.7565789,31.7333334 29.7105263,33.2666667 29.7105263,35.8 C29.7105263,38.3333333 27.8289474,40 24.8618421,40 C21.4605263,40 19,37.7333333 19,33.7333333 C19,28.0666667 23.7039474,24.0666667 26.5263158,22 Z M39.5263158,22 L43,25.2 C40.8289474,26.6666711 38.5131641,28.8666667 37.9342105,31.4 C40.7565789,31.7333334 42.7105263,33.2666667 42.7105263,35.8 C42.7105263,38.3333333 40.8289474,40 37.8618421,40 C34.4605263,40 32,37.7333333 32,33.7333333 C32,28.0666667 36.7039474,24.0666667 39.5263158,22 Z" transform="translate(-19 -22)"/></svg>
                        </div>
                        <span class="mt-8 leading-[50px] font-bold">{!! $content !!}</span>
                        @if($reference)
                            <span class="mt-8 text-lg tracking-wide">{{ $reference }}</span>
                        @endif
                    </div>

                @elseif($text_type == 'normal')

                    @if($title && in_array('title', $content_items))
                        <{{ $heading }} class="mb-6">{{ $title }}</{{ $heading }}>
                    @endif
                    <div>
                        {!! $content !!}
                    </div>

                @endif

                {{-- Buttons --}}
                @if($buttons && in_array('buttons', $content_items))
                    <div class="flex flex-wrap gap-6 mt-6 md:mt-10 {{( $text_type == 'quote' ? 'justify-center' : '' )}}">
                        @foreach($buttons as $button)
                            <x-button type="{{ $button['type'] }}" href="{{ $button['link']['url'] }}" target="{{ $button['link']['target'] }}">{!! $button['link']['title'] !!}</x-button>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </div>
</section>