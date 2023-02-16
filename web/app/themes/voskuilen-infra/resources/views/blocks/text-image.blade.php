<section class="relative {{ $pt }} {{ $pb }} bg-{{ $background }}">
    @if($layout == 'horizontal')
        <div class="container relative py-16 md:py-24 lg:py-32">
            <div class="w-full breakout-container bg-{{ $background == 'white' ? 'offwhite' : 'white'}} h-full absolute right-40 md:right-[18rem] top-1/2 -translate-y-1/2 z-0"></div>
            <div class="flex justify-between gap-12 md:gap-24 relative z-10">
                <div class="flex flex-col justify-center items-center">
                    <div class="max-w-lg">
                        @if($title && in_array('title', $content_items))
                            <h3 class="mb-6">{{ $title }}</h3>
                        @endif
                        @if($content && in_array('content', $content_items))
                            <span class="prose-lg">{!! $content !!}</span>
                        @endif
                        @if($links && in_array('links', $content_items))
                            <ul class="mt-12 flex flex-col gap-4 text-lg">
                                @foreach($links as $link)
                                    <a href="{{ $link['item']['url'] }}">
                                        <li class="relative flex items-center gap-3 border-b border-gray-200 pb-3 group">
                                            <svg class="h-4 mb-1 duration-150 text-black group-hover:translate-x-1 group-hover:text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                                            </svg>
                                            <span>{{ $link['item']['title'] }}</span>
                                        </li>
                                    </a>
                                @endforeach
                            </ul>
                        @endif
                        @if($buttons && in_array('buttons', $content_items))
                            <div class="flex flex-wrap gap-6 mt-6 md:mt-10">
                                @foreach($buttons as $button)
                                    <x-button type="{{ $button['type'] }}" href="{{ $button['link']['url'] }}" target="{{ $button['link']['target'] }}">{!! $button['link']['title'] !!}</x-button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
                <div class="flex flex-col lg:flex-row justify-center items-center gap-12 lg:gap-4">
                    <div class="relative bg-primary {{ $links ? "h-full lg:h-[50rem] w-full lg:w-[36rem] aspect-square md:aspect-video" : "h-full w-full aspect-video" }}">
                        {!! wp_get_attachment_image( $image['ID'], isset($size), "", ["class" => "w-full h-full absolute inset-0 object-cover object-center"] ) !!}
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="container">
            <div class="grid grid-cols-12">
                <div class="relative col-span-12 col-start-6 lg:col-end-13 aspect-[4/3]">
                    {!! wp_get_attachment_image( $image['ID'], isset($size), "", ["class" => "w-full h-full object-cover object-center]"] ) !!}
                </div>
                <div class="relative col-span-12 lg:col-span-7 z-10 -mt-40 p-24 bg-white">
                    @if($title && in_array('title', $content_items))
                        <h3 class="mb-6">{{ $title }}</h3>
                    @endif
                    @if($content && in_array('content', $content_items))
                        <span class="prose-lg leading-9">{!! $content !!}</span>
                    @endif
                    @if($buttons && in_array('buttons', $content_items))
                        <div class="flex flex-wrap gap-6 mt-6 md:mt-10">
                            @foreach($buttons as $button)
                                <x-button type="{{ $button['type'] }}" href="{{ $button['link']['url'] }}" target="{{ $button['link']['target'] }}">{!! $button['link']['title'] !!}</x-button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</section>