@extends('layouts.app')

@section('content')
  @include('partials.page-header')
  <div class="py-32 bg-white">
    <div class="container">
      @if (! have_posts())
      <x-alert type="warning">
        {!! __('Sorry, no results were found.', 'sage') !!}
      </x-alert>
      @endif
      <div class="grid grid-cols-3 gap-5 pb-12 border-b border-b-200 mb-6">
        @while(have_posts()) @php(the_post())
          @includeFirst(['partials.content-' . get_post_type(), 'partials.content'])
        @endwhile
        @php(wp_reset_postdata())
      </div>
      <div class="flex justify-between">
        <span></span>
        <?php next_posts_link( '<svg class="h-4 w-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"/></svg>' ); ?>
        <?php previous_posts_link( '<svg class="h-4 w-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15m0 0l6.75 6.75M4.5 12l6.75-6.75"/></svg>' ); ?>
      </div>
    </div>
  </div>
@endsection
