<article @php(post_class('relative w-full h-full'))>
  {!! wp_get_attachment_image(get_post_thumbnail_id(), 'large', false, ['class' => 'absolute inset-0 w-full h-full object-center']) !!}
  <div class="relative w-full h-full p-10">
    
  </div>
  {{-- <header>
    <h2 class="entry-title">
      <a href="{{ get_permalink() }}">
        {!! $title !!}
      </a>
    </h2>

    @include('partials.entry-meta')
  </header>

  <div class="entry-summary">
    @php(the_excerpt())
  </div> --}}
</article>