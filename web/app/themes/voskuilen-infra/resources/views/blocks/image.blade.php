<section class="relative {{ $pt }} {{ $pb }} bg-{{ $background }}">
    @if($layout == 'two_column')
        <div class="flex flex-col lg:flex-row justify-center items-center gap-12 lg:gap-4">
            <div class="relative aspect-square bg-primary h-full w-full md:w-[40rem] lg:w-[30rem]">
                {!! wp_get_attachment_image( $images[0]['ID'], isset($size), "", ["class" => "w-full h-full absolute inset-0 object-cover object-center"] ) !!}
            </div>
            <div class="relative aspect-square md:aspect-video bg-primary h-full md:h-[50rem] w-full md:w-[40rem] lg:w-[30rem]">
                {!! wp_get_attachment_image( $images[1]['ID'], isset($size), "", ["class" => "w-full h-full absolute inset-0 object-cover object-center"] ) !!}
            </div>
        </div>
    @elseif($layout == 'two_diagonal')
        <div class="container grid grid-cols-12 sm:gap-8 lg:gap-0">
            <div class="relative col-span-12 sm:col-span-11 lg:col-span-7 z-10 mb-8 sm:mb-0">
                {!! wp_get_attachment_image( $images[0]['ID'], isset($size), "", ["class" => "aspect-video object-cover object-center border-". $background ." lg:border-r-[1.25rem] lg:border-b-[1.25rem]"] ) !!}
                <div class="flex flex-col">
                    <span class="text-lg">{{ $images[0]['title'] }}</span>
                    <span>{{ $images[0]['caption'] }}</span>
                </div>
            </div>
            <div class="relative col-span-12 sm:col-start-2 sm:col-end-13 lg:col-start-6 lg:col-end-13 lg:-mt-48">
                <div class="flex flex-col text-right">
                    <span class="text-lg">{{ $images[1]['title'] }}</span>
                    <span>{{ $images[1]['caption'] }}</span>
                </div>
                {!! wp_get_attachment_image( $images[1]['ID'], isset($size), "", ["class" => "aspect-video object-cover object-center border-". $background ." lg:border-t-[1.25rem] lg:border-l-[1.25rem]"] ) !!}
            </div>
        </div>
    @elseif($layout == 'two_by_two')
        <div class="container grid grid-cols-12">
            @php
                $classes = [
                    'col-span-12 sm:col-start-1 sm:col-end-7',
                    'col-span-12 sm:col-start-9 sm:col-end-13',
                    'col-span-12 sm:col-start-1 sm:col-end-5',
                    'col-span-12 sm:col-start-7 sm:col-end-13'
                ];
                $sizes = [
                    'aspect-[3/4]',
                    'aspect-square',
                    'aspect-square',
                    'aspect-[3/4]'
                ];
            @endphp
            @foreach ($images as $image)
                <div class="flex flex-col mb-8 justify-center {{ $classes[$loop->index] }}">
                    {!! wp_get_attachment_image($image['ID'], isset($size), false, ['class' => 'w-full object-cover object-center '. $sizes[$loop->index]]) !!}
                    <div class="flex flex-col mt-3 sm:mt-5">
                        <span class="text-lg">
                            {{ $image['title'] }}
                        </span>
                        <span>
                            {{ $image['caption'] }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    @elseif($layout == 'single')
        <div class="{{ $width == 'wide' ? 'container' : 'px-5' }}">
            {!! wp_get_attachment_image($image['ID'], isset($size), false, ['class' => 'w-full aspect-video object-cover object-center']) !!}
        </div>
    @elseif($layout == 'slider')
        <!-- Slider main container -->
        <div class="{{ $width == 'wide' ? 'container' : 'px-5' }} swiper swiper-slider">
            <!-- Additional required wrapper -->
            <div class="swiper-wrapper">
                <!-- Slides -->
                @foreach($images as $image)
                    <div class="swiper-slide">
                        {!! wp_get_attachment_image($image['ID'], isset($size), false, ['class' => 'w-full aspect-video object-cover object-center']) !!}
                    </div>
                @endforeach
            </div>
            <!-- If we need pagination -->
            <div class="container flex mt-4">
                <div class="ml-auto swiper-pagination"></div>
            </div>
        </div>  
    @endif
</section>