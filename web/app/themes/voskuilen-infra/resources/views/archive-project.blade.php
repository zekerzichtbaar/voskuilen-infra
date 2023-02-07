@extends('layouts.app')

@section('content')
  @include('partials.page-header')
  @php
    $position = [
      'md:col-span-5',
      'md:col-span-7',
      'md:col-span-6',
      'md:col-span-6',
      'md:col-span-7',
      'md:col-span-5',
    ];
  @endphp

  <div class="container py-20 flex gap-4">
    {!! get_search_form(false) !!}
    <x-button type="primary" custom-icon="true">
      Filteren
      <svg class="h-6 w-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
      </svg>
    </x-button>
  </div> 
  <div class="py-32 bg-white">
    <div class="container">
      <div class="grid grid-cols-12 gap-4 pb-12 border-b border-b-200 mb-6" style="grid-template-rows: repeat(auto-fit, 500px 500px 500px 500px 500px 500px)">
        @if (! have_posts())
          <x-alert type="warning">
            {!! __('Sorry, no results were found.', 'sage') !!}
          </x-alert>
        @endif
        @while(have_posts()) @php(the_post())
          <div class="col-span-12 md:col-span-6">
            @includeFirst(['partials.content-' . get_post_type(), 'partials.content'])
          </div>
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
