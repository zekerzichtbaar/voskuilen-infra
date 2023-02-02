<?php

namespace WPMerge\PluginUpdateChecker;

if (!\class_exists('WPMerge\\PluginUpdateChecker\\Parsedown', \false)) {
    //Load the Parsedown version that's compatible with the current PHP version.
    if (\version_compare(\PHP_VERSION, '5.3.0', '>=')) {
        require __DIR__ . '/ParsedownModern.php';
    } else {
        require __DIR__ . '/ParsedownLegacy.php';
    }
}
