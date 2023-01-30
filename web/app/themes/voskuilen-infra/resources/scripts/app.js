import domReady from '@roots/sage/client/dom-ready';
import $ from 'jquery';

/**
 * Application entrypoint
 */
domReady(async () => {

  // Hamburger menu
  $('#menu').on('click', function() {
    $('#hamburger').children('.icon-left').children('span').toggleClass('icon-left--line');
    $('#hamburger').children('.icon-right').children('span').toggleClass('icon-right--line');
  })

});

/**
 * @see {@link https://webpack.js.org/api/hot-module-replacement/}
 */
import.meta.webpackHot?.accept(console.error);
