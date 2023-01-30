<section class="relative border-[30px] border-white py-0">
    @if($type == "header")
        <div class="text-white" style="height: 80vh;">
            @if($background = "bg_image")
                @if($bg_image){!! wp_get_attachment_image( $bg_image['ID'], isset($size), "", ["class" => "w-full h-full absolute inset-0 object-cover"] ) !!}@endif
                <div class="absolute inset-0 bg-gradient-to-b from-black/70 via-transparent to-black/70"></div>
            @else
                @if($bg_image)<video autoplay muted src="{{ $bg_video }}" class="w-full h-full absolute inset-0 object-cover"</>@endif
                <div class="absolute inset-0 bg-gradient-to-b from-black/70 via-transparent to-black/70"></div>
            @endif
            <div class="max-w-[95vw] md:max-w-[80vw] mx-auto h-full flex items-center">
                <h1 class="relative">{!! $title !!}</h1>
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
        <div class="text-white" style="height: 50vh;">
            @if($bg_image){!! wp_get_attachment_image( $bg_image['ID'], isset($size), "", ["class" => "w-full h-full absolute inset-0 object-cover"] ) !!}@endif
            <div class="absolute inset-0 bg-gradient-to-b from-black/70 via-transparent to-black/70"></div>
            <div class="max-w-[95vw] md:max-w-[80vw] mx-auto h-full flex items-center">
                <h1 class="relative">{!! $title !!}</h1>
            </div>
        </div>
    @endif
</section>