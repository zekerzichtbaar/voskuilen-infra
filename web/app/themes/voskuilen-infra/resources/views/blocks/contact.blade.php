<section class="relative {{ $pt }} {{ $pb }} bg-{{ $background }}">
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
            @if($buttons && in_array('buttons', $content_items))
                <div class="flex flex-wrap gap-6 mt-6 md:mt-10">
                    @foreach($buttons as $button)
                        <x-button type="{{ $button['type'] }}" href="{{ $button['link']['url'] }}" target="{{ $button['link']['target'] }}">{!! $button['link']['title'] !!}</x-button>
                    @endforeach
                </div>
            @endif
            @if($office_content && in_array('office_content', $content_items))
                <div class="grid md:grid-cols-2 md:gap-x-8 gap-y-10 md:gap-y-20 {{ count($content_items) !== 1 && $content_items[0] !== 'office_content' ? "my-12 md:my-24" : "" }}">
                    @foreach($office_content as $item)
                        <div class="flex flex-col col-span-1 gap-6 md:gap-12">
                            <div>
                                <h5 class="uppercase text-black/60">{{ $item['title'] }}</h5>
                                <svg class="w-full h-full text-black/20" xmlns="http://www.w3.org/2000/svg" width="781" height="1" viewBox="0 0 781 1"><line x1=".5" x2="780.5" y1="88.5" y2="88.5" fill="none" stroke="currentColor" stroke-linecap="square" transform="translate(0 -88)"/></svg>
                            </div>
                            <div>
                                {!! $item['text'] !!}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</section>