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
    <x-button type="primary">Filteren</x-button>
  </div> 
  <div class="py-32 bg-white">
    <div class="container grid grid-cols-12 gap-4" style="grid-template-rows: repeat(auto-fit, 500px 500px 500px 500px 500px 500px)">
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
      
      {!! get_the_posts_navigation() !!}
    </div>
  </div>
@endsection
