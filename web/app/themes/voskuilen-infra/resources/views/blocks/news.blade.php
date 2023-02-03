<section class="relative overflow-x-hidden py-32">
    <div class="container">
        <div class="swiper !overflow-visible">
            <div class="flex flex-col md:flex-row justify-between md:items-center mb-8">
                <{{ $heading }}>{{ $title }}</{{ $heading }}>
                <div class="swiper-pagination"></div>
            </div>
            <div class="block h-px w-full bg-gray-300 mb-12"></div>
            <div class="swiper-wrapper w-full">
                @while($item = $news->have_posts()) @php($news->the_post())
                    <div class="swiper-slide">
                        <div class="h-[600px]">
                            @includeFirst(['partials.content'])
                        </div>
                    </div>
                @endwhile
                @php(wp_reset_postdata())
            </div>
        </div>
    </div>
</section>