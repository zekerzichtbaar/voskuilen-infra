<form role="search" method="get" class="search-form w-full flex relative">
  <label for="search">
    <span class="sr-only">
      {{ _x('Search for:', 'label', 'sage') }}
    </span>
  </label>

  <input
    id="search"
    class="px-4 md:px-8 py-2 md:py-5 w-full"
    type="search"
    placeholder="{!! esc_attr_x('Zoeken &hellip;', 'placeholder', 'sage') !!}"
    value="{{ get_search_query() }}"
    name="search"
  >

  <button class="px-4 bg-white text-primary absolute inset-y-1 right-1">
    <svg class="h-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor" ariaHidden="true">
      <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
    </svg>
  </button>
</form>
