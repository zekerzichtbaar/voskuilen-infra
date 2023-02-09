<!doctype html>
<html <?php language_attributes(); ?>>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
    <link
      rel="preload"
      as="font"
      href="<?php echo (get_theme_file_uri()); ?>/resources/fonts/SofiaPro.woff"
      href="<?php echo (get_theme_file_uri()); ?>/resources/fonts/SofiaPro-Black.woff"
      href="<?php echo (get_theme_file_uri()); ?>/resources/fonts/SofiaPro-Bold.woff"
      href="<?php echo (get_theme_file_uri()); ?>/resources/fonts/SofiaPro-SemiBold.woff"
      type="font/woff"
      crossorigin="anonymous"
    >
  </head>
  <style>
    @font-face {
      font-family: 'Sofia Pro';
      src: local('Sofia Pro'), url('<?php echo (get_theme_file_uri()); ?>/resources/fonts/SofiaPro.woff') format('woff');
      font-weight: normal;
      font-style: normal;
    }
    @font-face {
      font-family: 'Sofia Pro';
      src: local('Sofia Pro'), url('<?php echo (get_theme_file_uri()); ?>/resources/fonts/SofiaPro-Black.woff') format('woff');
      font-weight: 900;
      font-style: normal;
    }
    @font-face {
      font-family: 'Sofia Pro';
      src: local('Sofia Pro'), url('<?php echo (get_theme_file_uri()); ?>/resources/fonts/SofiaPro-Bold.woff') format('woff');
      font-weight: bold;
      font-style: normal;
    }
    @font-face {
      font-family: 'Sofia Pro';
      src: local('Sofia Pro'), url('<?php echo (get_theme_file_uri()); ?>/resources/fonts/SofiaPro-SemiBold.woff') format('woff');
      font-weight: 600;
      font-style: normal;
    }
  </style>
  <body class="bg-offwhite">
    <?php wp_body_open(); ?>
    <?php do_action('get_header'); ?>

    <div id="app">
      <?php echo view(app('sage.view'), app('sage.data'))->render(); ?>
    </div>

    <?php do_action('get_footer'); ?>
    <?php wp_footer(); ?>
  </body>
</html>
