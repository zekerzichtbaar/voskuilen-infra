@if(!is_front_page())
  <div class="relative text-white border-[15px] md:border-[30px] border-b-0 border-transparent" style="height: 50vh;">
    {!! wp_get_attachment_image( get_post_thumbnail_id(), isset($size), "", ["class" => "w-full h-full absolute inset-0 object-cover"] ) !!}
    <div class="absolute inset-0 bg-gradient-to-b from-black/70 via-transparent to-black/70"></div>
    <div class="container mx-auto h-full flex items-center">
        <h1 class="relative">{!! $title !!}</h1>
    </div>
  </div>
  <div class="container -mt-[30px]">
    <?php
        if ( function_exists('yoast_breadcrumb') ) {
            yoast_breadcrumb( '<div class="pb-4 pt-5 border-b border-gray-200" id="breadcrumbs">','</div>' );
        }
    ?>
  </div>
@endif