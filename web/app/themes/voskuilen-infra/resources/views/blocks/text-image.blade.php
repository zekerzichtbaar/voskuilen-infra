<section class="relative {{ $pt }} {{ $pb }}">
    <div class="container">
        <div class="flex flex-col justify-center items-center">
            @if($content && in_array('content', $content_items))
                <div class="max-w-2xl">
                    <span class="prose content">{!! $content !!}</span>
                    @if($buttons && in_array('buttons', $content_items))
                        <div class="flex flex-wrap gap-6 mt-6 md:mt-10">
                            @foreach($buttons as $button)
                                <x-button type="{{ $button['type'] }}" href="{{ $button['link']['url'] }}" target="{{ $button['link']['target'] }}">{!! $button['link']['title'] !!}</x-button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>
        <div class="flex flex-col lg:flex-row justify-center items-center gap-12 lg:gap-4 mt-24">
            <div class="relative aspect-square bg-primary h-full w-full md:w-[40rem] lg:w-[30rem]">
                {!! wp_get_attachment_image( $two_column_image_one['ID'], isset($size), "", ["class" => "w-full h-full absolute inset-0 object-cover object-center"] ) !!}
            </div>
            <div class="relative aspect-video bg-primary h-[80vh] w-full md:w-[40rem] lg:w-[30rem]">
                {!! wp_get_attachment_image( $two_column_image_two['ID'], isset($size), "", ["class" => "w-full h-full absolute inset-0 object-cover object-center"] ) !!}
            </div>
        </div>
    </div>
</section>