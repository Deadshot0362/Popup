<?php

declare(strict_types=1);

namespace PopupPilot;

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('POPUPPILOT_VERSION')) {
    define('POPUPPILOT_VERSION', '0.1.0');
}

if (!defined('POPUPPILOT_FILE')) {
    define('POPUPPILOT_FILE', dirname(__DIR__) . '/popuppilot.php');
}

if (!defined('POPUPPILOT_PATH')) {
    define('POPUPPILOT_PATH', plugin_dir_path(POPUPPILOT_FILE));
}

if (!defined('POPUPPILOT_URL')) {
    define('POPUPPILOT_URL', plugin_dir_url(POPUPPILOT_FILE));
}

if (!defined('POPUPPILOT_ENV')) {
    $environmentType = function_exists('wp_get_environment_type') ? wp_get_environment_type() : 'production';
    define('POPUPPILOT_ENV', $environmentType);
}
