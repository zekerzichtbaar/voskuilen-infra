<section class="relative py-32 {{ $pt }} {{ $pb }} bg-{{ $background }}">
    <div class="container">
        <div class="flex flex-col md:flex-row justify-between md:items-center mb-12">
            <{{ $heading }}>{{ $title }}</{{ $heading }}>
            <x-button class="self-end" type="primary" href="/projecten/">{{ __('Naar alle projecten', 'voskuilen-infra') }}</x-button>
        </div>
        <div class="grid grid-cols-12 gap-4" style="grid-template-rows: repeat(auto-fit, 400px 500px 400px 500px 400px 500px">
            @php($index = 0)

            @while($item = $projects->have_posts()) @php($projects->the_post())
                <div class="col-span-12 {{ $position[$index] }}">
                    @includeFirst(['partials.content-' . get_post_type()])
                </div>
                @php(($index == 6 ? $index = 0 : $index++))
            @endwhile
            @php(wp_reset_postdata())
        </div>
    </div>
</section>