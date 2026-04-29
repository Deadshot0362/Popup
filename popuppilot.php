<?php
/**
 * Plugin Name: PopupPilot
 * Plugin URI: https://example.com/popuppilot
 * Description: WordPress popup builder plugin with visual editor, targeting, campaigns, and analytics.
 * Version: 0.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.3
 * Author: PopupPilot Team
 * Author URI: https://example.com
 * Text Domain: popuppilot
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/bootstrap/constants.php';
require_once POPUPPILOT_PATH . 'includes/bootstrap/class-env.php';
require_once POPUPPILOT_PATH . 'includes/class-capabilities.php';
require_once POPUPPILOT_PATH . 'includes/class-post-types.php';
require_once POPUPPILOT_PATH . 'includes/class-popup-schema-validator.php';
require_once POPUPPILOT_PATH . 'includes/api/class-routes.php';
require_once POPUPPILOT_PATH . 'includes/campaigns/class-routes.php';
require_once POPUPPILOT_PATH . 'includes/analytics/class-events.php';
require_once POPUPPILOT_PATH . 'includes/class-runtime.php';
require_once POPUPPILOT_PATH . 'admin/class-menu.php';
require_once POPUPPILOT_PATH . 'includes/class-popuppilot-plugin.php';

register_activation_hook(POPUPPILOT_FILE, ['PopupPilot\\Plugin', 'activate']);
register_deactivation_hook(POPUPPILOT_FILE, ['PopupPilot\\Plugin', 'deactivate']);

PopupPilot\\Plugin::instance()->boot();
