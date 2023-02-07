<section class="relative py-0">
    @if($type == "header")
        <div class="relative text-white border-[15px] md:border-[30px] border-transparent" style="height: 80vh;">
            @if($background = "bg_image")
                @if($bg_image){!! wp_get_attachment_image( $bg_image['ID'], isset($size), "", ["class" => "w-full h-full absolute inset-0 object-cover"] ) !!}@endif
                <div class="absolute inset-0 bg-gradient-to-b from-black/70 via-transparent to-black/70"></div>
            @else
                @if($bg_image)<video autoplay muted src="{{ $bg_video }}" class="w-full h-full absolute inset-0 object-cover"</>@endif
                <div class="absolute inset-0 bg-gradient-to-b from-black/70 via-transparent to-black/70"></div>
            @endif
            <div class="container mx-auto h-full flex">
                <h1 class="relative mt-auto mb-28">{!! $title !!}</h1>
                <div class="absolute bottom-0 translate-y-1/2 text-white flex flex-wrap gap-6">
                    @if($buttons)
                        @foreach($buttons as $button)
                            <x-button type="{{ $button['type'] }}" href="{{ $button['link']['url'] }}" target="{{ $button['link']['target'] }}">{!! $button['link']['title'] !!}</x-button>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    @elseif($type == "subheader")
        <div class="relative text-white border-[15px] md:border-[30px] border-b-0 border-transparent" style="height: 50vh;">
            @if($bg_image){!! wp_get_attachment_image( $bg_image['ID'], isset($size), "", ["class" => "w-full h-full absolute inset-0 object-cover"] ) !!}@endif
            <div class="absolute inset-0 bg-gradient-to-b from-black/70 via-transparent to-black/70"></div>
            <div class="container mx-auto h-full flex items-center">
                <h1 class="relative">{!! $title !!}</h1>
            </div>
        </div>
        <div class="container -mt-[30px]">
            @if(function_exists('yoast_breadcrumb'))
                @php(yoast_breadcrumb( '<div class="pb-4 pt-5" id="breadcrumbs">','</div>' ))
            @endif
            <hr>
        </div>
    @endif
</section>