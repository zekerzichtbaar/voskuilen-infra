<article @php(post_class('relative w-full h-full overflow-hidden'))>
  <a href="{{ get_permalink() }}" class="flex flex-col absolute inset-0 w-full h-full">
    <div class="relative flex p-10 w-full h-full">
      {!! wp_get_attachment_image(get_post_thumbnail_id(), 'large', false, ['class' => 'absolute inset-0 w-full h-full object-center object-cover duration-300 group-hover:scale-[1.1]']) !!}
      <div class="relative w-full flex items-start">
        @if(true)
          <div class="flex items-center justify-center self-start aspect-square p-1.5 border-white border-[2px]">
            <svg width="12" height="12" viewBox="0 0 12 14" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 7L3.01142e-07 13.9282L9.06825e-07 0.0717964L12 7Z" fill="white"/>
            </svg>
          </div>
        @endif
        
        <div class="ml-auto flex flex-wrap justify-end gap-2">
          @if(get_the_terms(get_the_ID(), 'category'))
            @foreach(get_the_terms(get_the_ID(), 'category') as $term)
              <div class="inline-block px-2 pt-1 border-[2px] border-white text-sm text-white uppercase font-bold">{{ $term->name }}</div>
            @endforeach
          @endif
          <div class="inline-block px-2 pt-1 border-[2px] border-primary text-sm bg-primary text-white uppercase font-bold">{{ __('Nieuws', 'voskuilen-infra') }}</div>
        </div>
      </div>
    </div>
    <div class="relative bg-white text-black p-10 h-full flex flex-col max-h-[40%]">
      <{{ (empty($news_heading) ? 'h2' : $news_heading) }} class="mb-6">{!! $title !!}</{{ (empty($news_heading) ? 'h2' : $news_heading) }}>
      <div class="flex justify-between items-center mt-auto">
        <div class="flex items-center">
          <svg class="h-6 w-auto mr-2 mb-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
          </svg>
          <span>{{ the_date() }}</span>
        </div>
        <svg class="text-primary group-hover:translate-x-3 h-6 duration-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" class="w-6 h-6">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3" />
        </svg>
      </div>
    </div>
  </a>
</article>