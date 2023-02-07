<section class="relative bg-offwhite py-32 bg-{{ $background }}">
    <div class="container">
        <div class="flex flex-col md:flex-row justify-between md:items-center mb-12">
            <{{ $heading }}>{{ $title }}</{{ $heading }}>
        </div>
        <div class="flex flex-col">
            @foreach($werknemers as $werknemer)
                <div class="flex items-center py-6 border-b border-gray-200 last:border-none gap-6">
                    {!! get_the_post_thumbnail($werknemer->ID, 'thumbnail', ['class' => 'h-24 w-24 rounded-full']) !!}
                    <div class="flex flex-col md:flex-row w-full md:items-center gap-4">
                        <div class="flex flex-col mr-auto">
                            <span class="text-2xl font-bold mb-1">{{ $werknemer->post_title }}</span>
                            <span class="text-lg text-gray-500">{{ get_field('position', $werknemer->ID) }}</span>
                        </div>
                        <div class="flex gap-4">
                            @if($email = get_field('email', $werknemer->ID))
                                <a href="mailto:{{ $email }}" class="bg-primary border-2 border-primary hover:bg-transparent text-white hover:text-primary duration-150 p-3">
                                    <svg class="h-5 w-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                                    </svg>                                  
                                </a>
                            @endif
                            @if($phone = get_field('phone', $werknemer->ID))
                                <a href="tel:{{ $phone }}" class="bg-primary border-2 border-primary hover:bg-transparent text-white hover:text-primary duration-150 p-3">
                                    <svg class="h-5 w-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/>
                                    </svg>                                  
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>