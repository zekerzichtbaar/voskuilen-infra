<section class="relative border-[30px] border-white">
        <div class="{{ $type = "header" ? "pt-52" : "" }}">
            @if($background = "bg_image")
                @if($bg_image){!! wp_get_attachment_image( $bg_image['ID'], isset($size), "", ["class" => "w-full h-full absolute inset-0 object-cover"] ) !!}@endif
                <div class="absolute inset-x-0 bg-gradient-to-t from-black/70 to-transparent h-1/2 bottom-0 z-10"></div>
                <div class="absolute inset-x-0 bg-gradient-to-b from-black/70 to-transparent h-1/2 top-0 z-10"></div>
            @else
                @if($bg_image)<video autoplay muted src="{{ $bg_video }}" class="w-full h-full absolute inset-0 object-cover"</>@endif
                <div class="absolute inset-x-0 bg-gradient-to-t from-black/60 to-transparent h-1/2 bottom-0 z-10"></div>
                <div class="absolute inset-x-0 bg-gradient-to-b from-black/60 to-transparent h-1/2 top-0 z-10"></div>
            @endif
        </div>
        <div class="z-10 -bottom-[10.2rem] pl-24 relative text-white">
            <h1>{!! $title !!}</h1>
            <div class="mt-20 flex gap-4">
                @if($buttons)
                    @foreach($buttons as $button)
                        <x-button type="{{ $button['type'] }}" href="{{ $button['link']['url'] }}" target="{{ $button['link']['target'] }}">{!! $button['link']['title'] !!}</x-button>
                    @endforeach
                @endif
            </div>
        </div>
</section>