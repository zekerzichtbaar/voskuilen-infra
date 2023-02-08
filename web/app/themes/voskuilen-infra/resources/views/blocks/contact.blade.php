<section class="relative {{ $pt }} {{ $pb }}">
    <div class="container">
        <div class="flex flex-col mx-auto max-w-3xl">
            @if($title && in_array('title', $content_items))
                <h2 class="text-left mb-4 md:mb-12">{{ $title }}</h2>
            @endif
            @if($form && in_array('form', $content_items))
                <div>
                    {!! $form !!}
                </div>
            @endif
        </div>
    </div>
</section>