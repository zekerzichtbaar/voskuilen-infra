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
    if(!empty($query_vars = get_query_var('tax_query'))) {
      $active_categories = array_values(get_query_var('tax_query')[0]['terms']);
    }
  @endphp

  <div class="container py-20 flex flex-col gap-4">
    <div class="flex gap-4 w-full">
      @include('forms.project-search')
      <x-button type="primary" custom-icon="true" id="projectFiltersToggle" class="cursor-pointer">
        Filters
        <svg class="h-6 w-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
        </svg>
      </x-button>
    </div>
    <div id="projectFilters" class="overflow-hidden">
      <form class="flex flex-col items-start gap-6 border border-gray-200 p-6" method="GET">
        <div class="flex flex-wrap">
          <span class="w-full text-lg font-bold">{{ __('Categorieën:', 'voskuilen-infra')}}</span>
          @foreach(get_terms('project_category') as $category)
            <label for="{{ $category->slug }}" class="relative text-gray-600 italic flex items-center text-lg mr-8 pl-8 cursor-pointer">
              <input type="checkbox" name="cat[]" id="{{ $category->slug }}" value="{{ $category->slug }}" class="invisible absolute" {{ (!empty($active_categories) && in_array($category->slug, $active_categories) ? 'checked' : '')}}>
              <div class="checkbox-facade h-6 w-6 aspect-square border border-gray-200 absolute left-0 flex">
                <div class="h-3 w-3 aspect-square m-auto bg-primary duration-150 opacity-0 scale-0"></div>
              </div>
              {{ $category->name}}
            </label>
            {{-- <div class="relative">
              <input type="checkbox" name="cat[]" id="{{ $category->slug }}" value="{{ $category->slug }}" {{ (!empty($active_categories) && in_array($category->slug, $active_categories) ? 'checked' : '')}}>
              <label for="{{ $category->slug }}" class="relative text-gray-600 italic flex items-center text-lg mr-8 pl-8">
                {{ $category->name}}
              </label>
            </div> --}}
          @endforeach
        </div>
        <button type="submit" class="button button-primary">
          Filter
          <svg class="h-6 w-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"/>
          </svg>          
        </button>
      </form>
    </div>
    @if (! have_posts())
      <x-alert type="warning">
        {!! __('Sorry, er zijn geen resultaten gevonden.', 'voskuilen-infra') !!}
      </x-alert>
    @endif
  </div> 
  <div class="py-32 bg-white">
    <div class="container">
      <div class="grid grid-cols-12 gap-4 pb-12 border-b border-b-200 mb-6" style="grid-template-rows: repeat(auto-fit, 500px 500px 500px 500px 500px 500px)">
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
