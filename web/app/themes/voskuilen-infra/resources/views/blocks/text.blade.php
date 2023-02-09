<section class="relative {{ $pt }} {{ $pb }}">
    <div class="container flex justify-center gap-6">
        @if(isset($menu_title) || isset($menu_items))
            <div class="flex flex-col w-96">
                <span class="text-3xl font-semibold">{{ $menu_title }}</span>
                <ul class="mt-2 text-lg">
                    @foreach ($menu_items as $menu_item)
                        <li class="py-2">
                            <a href="{{ get_permalink($menu_item->ID) }}" class="flex items-center group">
                                <svg class="h-4 mr-1.5 mb-1 -ml-1 duration-150 text-black group-hover:translate-x-1 group-hover:text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                                </svg>
                                {{ $menu_item->post_title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="{{ isset($menu_title) || isset($menu_items) ? '' : 'max-w-4xl' }}">
            <{{ $heading }}>{{ $title }}</{{ $heading }}>
            <div class="text-lg prose max-w-none mt-6">
                {!! $content !!}
            </div>
        </div>
    </div>
</section>