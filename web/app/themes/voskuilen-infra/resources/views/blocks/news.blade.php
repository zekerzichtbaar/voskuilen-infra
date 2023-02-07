<section class="relative overflow-x-hidden py-32">
    <div class="container">
        <div class="swiper !overflow-visible">
            <div class="flex flex-col md:flex-row justify-between md:items-center border-b border-gray-200 pb-8 mb-12">
                <{{ $heading }}>{{ $title }}</{{ $heading }}>
                <div class="swiper-pagination"></div>
            </div>
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