import domReady from '@roots/sage/client/dom-ready';
import $ from 'jquery';
import * as basicScroll from 'basicscroll'

/**
 * Application entrypoint
 */
domReady(async () => {

  // Hamburger menu
  $('#menuBtn').on('click', function() {
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
        to: 0.8
      }
    }
  })
  
  instance.start()

});

/**
 * @see {@link https://webpack.js.org/api/hot-module-replacement/}
 */
import.meta.webpackHot?.accept(console.error);
