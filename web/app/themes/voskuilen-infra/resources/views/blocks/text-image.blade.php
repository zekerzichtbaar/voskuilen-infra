<section class="relative {{ $pt }} {{ $pb }}">
    <div class="container">
        <div class="flex flex-col gap-24">
            <div class="flex flex-col justify-center items-center {{ $layout == "text-image" ? "order-1" : "order-2" }}">
                <div class="{{ $image_layout == "two-column" ? "max-w-2xl" : "max-w-3xl" }}">
                    @if($title && in_array('title', $content_items))
                        <h3 class="mb-6">{{ $title }}</h3>
                    @endif
                    @if($content && in_array('content', $content_items))
                        <span class="prose {{ $image_layout == "two-column" ? "content" : "text-lg leading-9" }}">{!! $content !!}</span>
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
                        <div class="relative aspect-video bg-primary h-[80vh] w-full md:w-[40rem] lg:w-[30rem]">
                            {!! wp_get_attachment_image( $two_column_image_two['ID'], isset($size), "", ["class" => "w-full h-full absolute inset-0 object-cover object-center"] ) !!}
                        </div>
                    @endif
                </div>
            @else
                <div class="flex flex-col lg:flex-row justify-center items-center gap-12 lg:gap-4 {{ $layout == "text-image" ? "order-2" : "order-1" }}">
                    @if($one_column_image)
                        <div class="relative aspect-video bg-primary h-full w-full">
                            {!! wp_get_attachment_image( $one_column_image['ID'], isset($size), "", ["class" => "w-full h-full absolute inset-0 object-cover object-center"] ) !!}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</section>