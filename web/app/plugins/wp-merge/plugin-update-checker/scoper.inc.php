<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

// WPMerge Custom code starts here
/**
 * whitelist-global-functions is true, then in build/vendor/scoper-autoload.php is created after the following lines
 * // Functions whitelisting. For more information see:
 * // https://github.com/humbug/php-scoper/blob/master/README.md#functions-whitelisting
 * you can list of functions with if (!function_exists('wp_normalize_path')) {... codes. use that list here. You have create a build first.
 */
// WPMerge Custom code ENDS here

$wp_functions = ['wp_normalize_path', 'get_file_data', 'apply_filters', 'add_action', 'get_submit_button', 'esc_attr', 'wp_create_nonce', 'wp_next_scheduled', 'human_time_diff', 'get_option', 'add_filter', 'wp_enqueue_style', 'wp_enqueue_script', 'esc_html', 'is_wp_error', 'wp_remote_retrieve_response_code', 'wp_remote_retrieve_response_message', 'wp_remote_retrieve_headers', 'wp_remote_retrieve_body', 'check_ajax_referer', 'remove_filter', 'remove_action', 'get_theme_root', 'plugins_url', 'get_theme_root_uri', '_cleanup_header_comment', 'update_site_option', 'get_site_option', 'delete_site_option', 'did_action', 'is_admin', 'get_user_locale', 'get_locale', 'load_textdomain', 'get_core_updates', 'add_query_arg', 'wp_remote_get', 'do_action', 'get_available_languages', 'wp_get_installed_translations', 'trailingslashit', 'plugin_basename', 'translate', 'get_plugin_data', 'register_deactivation_hook', 'current_user_can', '__', 'esc_url', 'network_admin_url', 'wp_nonce_url', 'self_admin_url', 'check_admin_referer', 'set_site_transient', 'wp_redirect', '_x', 'get_site_transient', 'delete_site_transient', 'wp_get_theme', 'get_stylesheet', 'untrailingslashit', 'wp_schedule_event', 'wp_clear_scheduled_hook', 'current_filter', 'get_plugins', 'user_sanitize', 'balanceTags', 'wp_kses', 'encodeit', 'decodeit' ];

return [
    // The prefix configuration. If a non null value will be used, a random prefix will be generated.
    'prefix' => 'WPMerge\\PluginUpdateChecker',

    // By default when running php-scoper add-prefix, it will prefix all relevant code found in the current working
    // directory. You can however define which files should be scoped by defining a collection of Finders in the
    // following configuration key.
    //
    // For more see: https://github.com/humbug/php-scoper#finders-and-paths
    'finders' => [
        //Finder::create()->files()->in('src'),
        Finder::create()
            ->files()
            ->ignoreVCS(true)
            ->notName('/LICENSE|.*\\.md|.*\\.dist|Makefile|composer\\.json|scoper\\.inc\\.php|composer\\.lock/')
            ->exclude([
                'doc',
                'test',
                'test_old',
                'tests',
                'Tests',
                'vendor-bin',
            ])
            ->in('.'),
        Finder::create()->append([
            'composer.json',
        ]),
    ],

    // Whitelists a list of files. Unlike the other whitelist related features, this one is about completely leaving
    // a file untouched.
    // Paths are relative to the configuration file unless if they are already absolute
    'files-whitelist' => [
        'src/a-whitelisted-file.php',
    ],

    // When scoping PHP files, there will be scenarios where some of the code being scoped indirectly references the
    // original namespace. These will include, for example, strings or string manipulations. PHP-Scoper has limited
    // support for prefixing such strings. To circumvent that, you can define patchers to manipulate the file to your
    // heart contents.
    //
    // For more see: https://github.com/humbug/php-scoper#patchers
    'patchers' => [
        // WPMerge Custom code starts here
        function (string $filePath, string $prefix, string $contents) use ($wp_functions): string {
            // don't prefix native wp functions
            foreach($wp_functions as $wp_functions__value) {
                $contents = str_replace('\\'.$prefix.'\\'.$wp_functions__value.'(', '\\'.$wp_functions__value.'(', $contents);
            }
            // WPMerge Custom code ENDS here
            return $contents;
        },
    ],

    // PHP-Scoper's goal is to make sure that all code for a project lies in a distinct PHP namespace. However, you
    // may want to share a common API between the bundled code of your PHAR and the consumer code. For example if
    // you have a PHPUnit PHAR with isolated code, you still want the PHAR to be able to understand the
    // PHPUnit\Framework\TestCase class.
    //
    // A way to achieve this is by specifying a list of classes to not prefix with the following configuration key. Note
    // that this does not work with functions or constants neither with classes belonging to the global namespace.
    //
    // Fore more see https://github.com/humbug/php-scoper#whitelist
    'whitelist' => [
        // 'PHPUnit\Framework\TestCase',   // A specific class
        // 'PHPUnit\Framework\*',          // The whole namespace
        // '*',                            // Everything
    ],

    // If `true` then the user defined constants belonging to the global namespace will not be prefixed.
    //
    // For more see https://github.com/humbug/php-scoper#constants--constants--functions-from-the-global-namespace
    'whitelist-global-constants' => true,

    // If `true` then the user defined classes belonging to the global namespace will not be prefixed.
    //
    // For more see https://github.com/humbug/php-scoper#constants--constants--functions-from-the-global-namespace
    'whitelist-global-classes' => true,

    // If `true` then the user defined functions belonging to the global namespace will not be prefixed.
    //
    // For more see https://github.com/humbug/php-scoper#constants--constants--functions-from-the-global-namespace
    'whitelist-global-functions' => false,
];
