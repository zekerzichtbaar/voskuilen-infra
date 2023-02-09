<section class="relative {{ $pt }} {{ $pb }}">
    @if($links && in_array('links', $content_items))
        <svg class="absolute -z-10 inset-y-0 left-0 h-full w-full lg:w-[70%] text-offwhite" xmlns="http://www.w3.org/2000/svg" width="auto" height="full"><rect width="1510" height="1075" fill="currentColor" fill-rule="evenodd"/></svg>
    @endif
    <div class="container">
        <div class="flex gap-12 md:gap-24 {{ $links ? "flex-col lg:flex-row" : "flex-col" }}">
            <div class="flex flex-col justify-center items-center {{ $layout == "text-image" ? "order-1" : "order-2" }}">
                <div class="{{ $image_layout == "two-column" ? "max-w-2xl" : ($links ? 'max-w-4xl' : 'max-w-3xl')}}">
                    @if($title && in_array('title', $content_items))
                        <h3 class="mb-6">{{ $title }}</h3>
                    @endif
                    @if($content && in_array('content', $content_items))
                        <span class="prose {{ $image_layout == "two-column" ? "content" : "text-lg leading-9" }}">{!! $content !!}</span>
                    @endif
                    @if($links && in_array('links', $content_items))
                        <ul class="mt-12 flex flex-col gap-4">
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
            @if($image_layout == "two-column")
                <div class="flex flex-col lg:flex-row justify-center items-center gap-12 lg:gap-4 {{ $layout == "text-image" ? "order-2" : "order-1" }}">
                    @if($two_column_image_one)
                        <div class="relative aspect-square bg-primary h-full w-full md:w-[40rem] lg:w-[30rem]">
                            {!! wp_get_attachment_image( $two_column_image_one['ID'], isset($size), "", ["class" => "w-full h-full absolute inset-0 object-cover object-center"] ) !!}
                        </div>
                    @endif
                    @if($two_column_image_two)
                        <div class="relative aspect-square md:aspect-video bg-primary h-full md:h-[50rem] w-full md:w-[40rem] lg:w-[30rem]">
                            {!! wp_get_attachment_image( $two_column_image_two['ID'], isset($size), "", ["class" => "w-full h-full absolute inset-0 object-cover object-center"] ) !!}
                        </div>
                    @endif
                </div>
            @else
                <div class="flex flex-col lg:flex-row justify-center items-center gap-12 lg:gap-4 {{ $layout == "text-image" ? "order-2" : "order-1" }} {{ $links ? "w-full" : "" }}">
                    @if($one_column_image)
                        <div class="relative bg-primary {{ $links ? "h-full md:h-[50rem] w-full md:w-[40rem] lg:w-[30rem] aspect-square md:aspect-video" : "h-full w-full aspect-video" }}">
                            {!! wp_get_attachment_image( $one_column_image['ID'], isset($size), "", ["class" => "w-full h-full absolute inset-0 object-cover object-center"] ) !!}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</section>