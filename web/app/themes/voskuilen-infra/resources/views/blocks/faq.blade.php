<section class="relative {{ $pt }} {{ $pb }}">
    <div class="container">
        <div class="flex justify-center items-center mx-auto max-w-3xl">
            <h2>{{ $title }}</h2>
        </div>
        <div class="flex justify-center items-center mx-auto mt-20 max-w-3xl">
            @if($faqs)
                <ul>
                    @foreach($faqs as $item)
                        <div class="faq grid grid-cols-6 md:grid-cols-12 items-center gap-4 my-4 md:my-6">
                            <p class="inline-flex faq-iteration col-span-1 text-black/50 mb-8 transition duration-200 ease-in-out">
                                @if($loop->iteration <=9)
                                    <span>0</span>
                                @endif
                                {!! $loop->iteration !!}
                            </p>
                            <div class="flex flex-col gap-2 col-span-4 md:col-span-10">
                                <li class="question font-bold text-sm md:text-2xl transition duration-300 ease-in-out">{{ $item['question'] }}</li>
                                <li class="answer font-bold hidden text-sm md:text-2xl">{{ $item['answer'] }}</li>
                            </div>
                            <svg class="w-auto h-4 md:h-6 col-span-1 faq-arrow ml-8 transition duration-200 ease-in-out" xmlns="http://www.w3.org/2000/svg" width="12" height="26" viewBox="0 0 12 26"><path fill="none" stroke="#000" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M781,33 L786,38 M786,38 L781,43 M786,38 L762,38" transform="rotate(90 402.5 -358.5)"/></svg>
                        </div>
                        <svg class="mt-4 w-full h-full" xmlns="http://www.w3.org/2000/svg" width="781" height="1" viewBox="0 0 781 1"><line x1=".5" x2="780.5" y1="88.5" y2="88.5" fill="none" stroke="#E1E1E1" stroke-linecap="square" transform="translate(0 -88)"/></svg>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</section>