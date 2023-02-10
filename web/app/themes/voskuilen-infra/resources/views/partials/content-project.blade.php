<article @php(post_class('relative w-full h-full overflow-hidden group min-h-[300px]'))>
  {!! wp_get_attachment_image(get_post_thumbnail_id(), 'large', false, ['class' => 'absolute inset-0 w-full h-full object-center object-cover duration-300 group-hover:scale-[1.1]']) !!}
  <div class="absolute inset-0 w-full h-full bg-gradient-to-t from-black/60 via-transparent via-transparent to-black/60"></div>
  <a href="{{ get_permalink() }}" class="flex flex-col absolute inset-0 w-full h-full p-10 text-white">
    <div class="flex align-start">
      @if(true)
        <div class="flex items-center justify-center self-start aspect-square p-1.5 border-white border-[2px]">
          <svg width="12" height="12" viewBox="0 0 12 14" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 7L3.01142e-07 13.9282L9.06825e-07 0.0717964L12 7Z" fill="white"/>
          </svg>
        </div>
      @endif
      
      <div class="ml-auto flex flex-wrap justify-end gap-2">
        @if(get_the_terms(get_the_ID(), 'project_category'))
          @foreach(get_the_terms(get_the_ID(), 'project_category') as $term)
            <div class="inline-block px-2 pt-1 border-[2px] border-white text-sm text-white uppercase font-bold">{{ $term->name }}</div>
          @endforeach
        @endif
        <div class="inline-block px-2 pt-1 border-[2px] border-primary text-sm bg-primary text-white uppercase font-bold">{{ __('Projecten', 'voskuilen-infra') }}</div>
      </div>
    </div>
    <{{ (empty($project_heading) ? 'h3' : $project_heading) }} class="mt-auto mb-6">{!! $title !!}</{{ (empty($project_heading) ? 'h3' : $project_heading) }}>
    <div class="flex justify-between items-center">
      <span>Bekijk dit project</span>
      <svg class="group-hover:translate-x-3 h-6 duration-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#fff" class="w-6 h-6">
        <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3" />
      </svg>
    </div>
  </a>
</article>