<section class="relative {{ $pt }} {{ $pb }}">
    <div class="container">
        <div class="grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-4 justify-between items-center gap-10 md:gap-24 2xl:gap-4">
            @if($counter)
                @foreach($counter as $item)
                    <div class="flex flex-col gap-4 w-full">
                        @if($item['title'])
                            <span class="font-normal text-sm">{!! $item['title'] !!}</span>
                            <div class="w-full h-[2px] bg-black/10"></div>
                        @endif
                        @if($item['count'])
                            <span class="text-7xl font-semibold mt-1 md:mt-6">
                                <span id="{{ $loop->iteration }}" class="counter">{!! $item['count'] !!}</span>
                                @if($item['unit'])<span class="unit font-normal uppercase text-sm">{!! $item['unit'] !!}</span>@endif
                            </span>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</section>