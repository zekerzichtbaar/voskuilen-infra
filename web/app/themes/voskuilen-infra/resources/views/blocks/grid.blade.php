<section class="pt-32 bg-offwhite">
    <div class="container">
        <div class="grid grid-cols-12 gap-5 grid_template">
            @php $project_count = 0; @endphp
            @php $article_count = 0; @endphp
            @if(!empty($grid_items))
                @foreach ($grid_items as $grid_item)
                    <div class="col-span-12 {{ $position[$loop->index] }}">
                        @if($grid_item['acf_fc_layout'] == 'project')

                            @php
                                $projects = get_post_or_latest($grid_item['selected_project'], $project_count, 'project');
                                ($grid_item['selected_project'] == null ? $project_count++ : '');
                                $project_heading = $grid_item['project_heading'];
                            @endphp

                            @while($projects->have_posts()) @php($projects->the_post())
                                @includeFirst(['partials.content-' . get_post_type()])
                            @endwhile
                            
                            @php(wp_reset_postdata())
                        
                        @elseif($grid_item['acf_fc_layout'] == 'page_links')
                        
                            <div class="h-full p-6 sm:p-10 lg:p-16 xl:p-20 bg-white">
                                <h3 class="mb-3">{{ $grid_item['title'] }}</h3>
                                <ul class="grid grid-cols-1 sm:grid-cols-2 gap-x-8">
                                    @foreach($grid_item['selected_pages'] as $page)
                                        <li class="py-2 border-b mt-1">
                                            <a href="{{ get_permalink($post->ID) }}" class="flex items-center group">
                                                <svg class="h-4 mr-1.5 mb-1 -ml-1 duration-150 text-black group-hover:translate-x-1 group-hover:text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                                                </svg>
                                                {{ $page->post_title }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        
                        @elseif($grid_item['acf_fc_layout'] == 'expertises')
                            
                            <div class="grid grid-cols-2 grid-rows-2 gap-5">
                                @foreach($grid_item['expertises'] as $expertise)
                                    <a href="{{ $expertise['link']['url'] }}" class="bg-white aspect-square flex flex-col p-6 sm:p-10 md:p-6 xl:p-10 group">
                                        <div class="w-5 sm:w-7 md:w-5 lg:w-7">
                                            {!! $expertise['icon'] !!}
                                        </div>
                                        <h3 class="mt-auto lg:mb-6">{{ $expertise['link']['title'] }}</h3>
                                        <div class="flex item-center justify-between">
                                            <span class="text-sm lg:text-base">{{ __('Expertise bekijken', 'voskuilen-infra') }}</span>
                                            <svg class="group-hover:translate-x-3 h-6 duration-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#fff" class="w-6 h-6">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3" />
                                            </svg>
                                        </div>
                                    </a>
                                @endforeach
                            </div>

                        @elseif($grid_item['acf_fc_layout'] == 'news_article')

                            <?php
                                $articles = get_post_or_latest($grid_item['selected_article'], $article_count);
                                ($grid_item['selected_article'] == null ? $article_count++ : '');
                                $news_heading = $grid_item['news_heading'];
                            ?>

                            @while($articles->have_posts()) @php($articles->the_post())
                                @includeFirst(['partials.content'])
                            @endwhile
                            
                            <?php wp_reset_postdata(); ?>
                            
                        @elseif($grid_item['acf_fc_layout'] == 'text')

                            <a href="{{ $grid_item['link']['url'] }}" target="{{ $grid_item['link']['target'] }}" class="flex flex-col h-full items-start justify-between p-6 sm:p-10 bg-primary text-white group">
                                <h3 class="mb-6">{{ $grid_item['title'] }}</h3>
                                <div class="flex item-center justify-between w-full">
                                    <div>{!! $grid_item['text'] !!}</div>
                                    <svg class="group-hover:translate-x-3 h-6 duration-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#fff" class="w-6 h-6">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3" />
                                    </svg>
                                </div>
                            </a>

                        @endif
                    </div>
                @endforeach
            @endif
            {{-- <pre> --}}
            {{-- @php(print_r($project_count)) --}}
            {{-- @php(print_r($projects)) --}}
        </div>
    </div>
</section>