<div class="flex flex-wrap gap-2">
  @if(get_the_terms(get_the_ID(), 'category'))
    @foreach(get_the_terms(get_the_ID(), 'category') as $term)
      <div class="inline-block px-2 pt-1 border-[2px] border-primary bg-primary text-sm text-white uppercase font-bold">{{ $term->name }}</div>
    @endforeach
  @endif
</div>
<time class="updated flex items-center" datetime="{{ get_post_time('c', true) }}">
  <svg class="h-6 w-auto mr-2 mb-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
  </svg>
  {{ get_the_date() }}
</time>