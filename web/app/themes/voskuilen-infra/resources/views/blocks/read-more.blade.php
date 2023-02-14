<section class="relative {{ $pt }} {{ $pb }} bg-{{ $background }}">
    <div class="container">
        <{{ $heading }} class="text-center mb-20">{{ $title }}</{{ $heading }}>
        <div class="grid grid-cols-1 md:grid-cols-3 grid-rows-auto gap-5">
            @foreach($items as $item)
                <a href="{{ get_the_permalink($item['post']->ID) }}" class="relative flex flex-col group">
                    <div class="w-full aspect-video overflow-hidden mb-6">
                        {!! wp_get_attachment_image( get_post_thumbnail_id($item['post']->ID), isset($size), false, ["class" => "w-full h-full object-center object-cover duration-300 group-hover:scale-[1.1]"] ) !!}
                    </div>
                    <h4 class="flex items-center gap-2 mb-1">
                        {{ (!empty($item['optional_title']) ? $item['optional_title'] : $item['post']->post_title) }}
                        <svg class="text-primary group-hover:translate-x-3 h-6 duration-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" class="w-6 h-6">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3" />
                        </svg>
                    </h4>
                    <span class="font-normal text-base">{{ (!empty($item['optional_intro']) ? $item['optional_intro'] : $item['post']->post_excerpt) }}</span>
                </a>
            @endforeach
        </div>
    </div>
</section>