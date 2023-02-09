<section class="relative {{ $pt }} {{ $pb }}">
    <div class="flex justify-center items-center w-[95%] swiper mySwiper">
        <div class="swiper-wrapper">
            @foreach($slides as $item)
                <div class="swiper-slide relative aspect-video bg-primary h-full w-full">
                    {!! wp_get_attachment_image( $item['image']['ID'], isset($size), "", ["class" => "w-full h-full absolute inset-0 object-cover object-center"] ) !!}
                </div>
            @endforeach
        </div>
    </div>
</section>