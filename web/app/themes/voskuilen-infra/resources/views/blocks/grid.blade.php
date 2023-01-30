<section class="container">
    <div class="grid_template">
        @php
            $position = [
                'col-span-5 aspect-square',
                'col-span-7',
                'col-span-6 aspect-square',
                'col-span-6 aspect-square'
            ];
        @endphp
        @foreach ($grid_items as $grid_item)
            <div class="bg-white {{ $position[$loop->index] }}">
                @if($grid_item['acf_fc_layout'] == 'project')
                    @php($projects = get_post_or_latest())
                    @while($projects->have_posts()) @php($projects->the_post())
                        @includeFirst(['partials.content-' . get_post_type()])
                    @endwhile
                    @php(wp_reset_postdata())
                @elseif($grid_item['acf_fc_layout'] == 'page_links')
                    <div class="p-20">
                        <h2 class="text-3xl font-bold">{{ $grid_item['title'] }}</h2>
                        <ul>
                            @foreach($grid_item['selected_pages'] as $page)
                                <li class="py-2 mt-2 pl-8 border-b"><a href="{{ get_permalink($post->ID) }}">{{ $page->post_title }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endforeach
        <pre>
        {{-- @php(print_r($grid_items)) --}}
    </div>
</section>