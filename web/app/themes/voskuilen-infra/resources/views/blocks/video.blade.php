<section class="relative {{ $pt }} {{ $pb }}">
    <div class="container">
        <div class="flex justify-center items-center">
            <div class="relative w-full h-full aspect-video">
                <div class="z-10 absolute inset-0 flex items-center justify-center">
                    <div id="playBtn" class="border-4 border-white p-2 md:p-4">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" stroke-width="1.5" class="w-6 h-6 text-white">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" />
                        </svg>
                    </div>
                </div>
                <video id="video" class="w-full h-full" src="{!! $video_url !!}"></video>
                {!! wp_get_attachment_image( $placeholder['ID'], isset($size), "", ["class" => "placeholder w-full h-full absolute inset-0 object-cover"] ) !!}
                <div class="absolute inset-0 bg-black/20"></div>
                <div class="hidden controls">
                    <div id="togglePlayBtn" class="bg-primary p-3 md:p-4 absolute -bottom-12 md:-bottom-14 right-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="pauseBtn w-6 h-6 text-white">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
                        </svg>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" class="playBtn hidden w-6 h-6 text-white">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" />
                        </svg>
                    </div>
                    <div id="toggleAudioBtn" class="bg-primary p-3 md:p-4 absolute -bottom-12 md:-bottom-14 right-12 md:right-14">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="muteBtn hidden w-6 h-6 text-white">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 9.75L19.5 12m0 0l2.25 2.25M19.5 12l2.25-2.25M19.5 12l-2.25 2.25m-10.5-6l4.72-4.72a.75.75 0 011.28.531V19.94a.75.75 0 01-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.506-1.938-1.354A9.01 9.01 0 012.25 12c0-.83.112-1.633.322-2.395C2.806 8.757 3.63 8.25 4.51 8.25H6.75z" />
                        </svg>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="soundBtn w-6 h-6 text-white">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.114 5.636a9 9 0 010 12.728M16.463 8.288a5.25 5.25 0 010 7.424M6.75 8.25l4.72-4.72a.75.75 0 011.28.53v15.88a.75.75 0 01-1.28.53l-4.72-4.72H4.51c-.88 0-1.704-.507-1.938-1.354A9.01 9.01 0 012.25 12c0-.83.112-1.633.322-2.396C2.806 8.756 3.63 8.25 4.51 8.25H6.75z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>