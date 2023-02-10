<article @php(post_class())>
  <div class="w-full max-w-3xl mx-auto px-6 animate-marque">
    <div class="pt-24 pb-16 flex justify-between">
      @include('partials.entry-meta')
    </div>
  </div>
    @php(the_content())
</article>
