import domReady from '@roots/sage/client/dom-ready';
import $ from 'jquery';
import * as basicScroll from 'basicscroll';
import Swiper from 'swiper';
import { Pagination } from 'swiper';
import { CountUp } from 'countup.js';

/**
 * Application entrypoint
 */
domReady(async () => {

  // Hamburger menu
  $('#menuBtn').on('click', function() {
    $('body').toggleClass('overflow-y-hidden');
    $('#hamburger').children('.icon-left').children('span').toggleClass('icon-left--line');
    $('#hamburger').children('.icon-right').children('span').toggleClass('icon-right--line');
    $('#menuScreen').toggleClass('-translate-y-full opacity-0');
    if ($(window).width() >= 1024) {
      $('.nav-primary').fadeToggle('fast');
    }
  })

  // MENU IMAGE
  var menuId = $('.highlight-nav li.current_page_item').prop('id');
  $(`.menu-image[data-id=${menuId}]`).addClass('active');

  $('.highlight-nav li').hover(
    function () {
      var menuId = $(this).prop('id');
      $('.menu-image').removeClass('active');
      $(`.menu-image[data-id=${menuId}]`).addClass('active');
    },
    function () {
      var menuId = $('.highlight-nav li.current_page_item').prop('id');
      $('.menu-image').removeClass('active');
      $(`.menu-image[data-id=${menuId}]`).addClass('active');
    }
  );

  const instance = basicScroll.create({
    elem: document.querySelector('.scrolled'),
    from: '0',
	  to: '150px',
    props: {
      '--gradient': {
        from: 0,
        to: 0.65
      }
    }
  })
  
  instance.start()

  Swiper.use([Pagination]);

  const swiper = new Swiper('.swiper-news',  {
    slidesPerView: 3,
    spaceBetween: 20,
    grabCursor: true,
    preloadImages: true,
    pagination: {
      el: '.swiper-pagination',
      type: 'bullets',
      clickable: true,
    },
    slidesPerView: 1,
    spaceBetween: 20,
    breakpoints: {
      1024: {
        slidesPerView: 3,
        spaceBetween: 20
      },
      768: {
        slidesPerView: 2,
        spaceBetween: 20
      }
    },
  });

  const swiperFull = new Swiper(".swiper-slider", {
    slidesPerView: 1,
    grabCursor: true,
    preloadImages: true,
    loop: true,
    pagination: {
      el: '.swiper-pagination',
      type: 'bullets',
      clickable: true,
    },
  });

  $('.counter').each(function() {
    let id = $(this).attr('id');
    let val = $(this).text();

    new CountUp(id, val, {
      enableScrollSpy: true,
      scrollSpyDelay: 100,
      useGrouping: false,
      useEasing: true,
      scrollSpyOnce: true,
    });
  });
  
  $('#playBtn').on('click', function() {
    $('#video').trigger('play');
    $('#playBtn, .placeholder').fadeOut();
    $('.controls').fadeIn();
  });

  $('#togglePlayBtn').on('click', function() {
    var video = $("#video").get(0);

    if (video.paused) {
        $('#video').trigger('play');
        $('.pauseBtn').show();
        $('.playBtn').hide();
    } else {
      $('#video').trigger('pause');
      $('.pauseBtn').hide();
      $('.playBtn').show();
    }
  });

  $("#video").prop('muted', false);

  $('#toggleAudioBtn').on('click', function() {
    if($("#video").prop('muted')) {
      $("#video").prop('muted', false);
      $('.soundBtn').show();
      $('.muteBtn').hide();
    } else {
      $("#video").prop('muted', true);
      $('.soundBtn').hide();
      $('.muteBtn').show();
    }
  });

  $('#projectFiltersToggle').on('click', function() {
    $('#projectFilters').toggleClass('collapsed');
  });

  $('.faq').on('click', function() {
    // Reset all values
    $(this).siblings().find('.question').siblings('.answer').slideUp(200);
    $(this).siblings().find('.question').removeClass('text-black/30');
    $(this).siblings().find('.faq-arrow').removeClass('rotate-180');
    $(this).siblings().find('.faq-iteration').removeClass('!text-primary font-bold');
    // Set all values
    $(this).find('.question').siblings('.answer').slideToggle(200);
    $(this).find('.question').toggleClass('text-black/30');
    $(this).find('.faq-arrow').toggleClass('rotate-180');
    $(this).find('.faq-iteration').toggleClass('!text-primary font-bold');
  });

  var prevScrollpos = window.pageYOffset;
  let header = $("header");
  window.onscroll = function() {
    var currentScrollPos = window.pageYOffset;
    if (prevScrollpos > currentScrollPos) {
      header.removeClass('-translate-y-full');
    } else {
      header.addClass('-translate-y-full');
    }
    prevScrollpos = currentScrollPos;
  } 

});

/**
 * @see {@link https://webpack.js.org/api/hot-module-replacement/}
 */
import.meta.webpackHot?.accept(console.error);
