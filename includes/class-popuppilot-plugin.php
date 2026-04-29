<?php

declare(strict_types=1);

namespace PopupPilot;

use PopupPilot\Admin\Menu;
use PopupPilot\Analytics\Events;
use PopupPilot\Api\Routes;
use PopupPilot\Campaigns\Routes as CampaignRoutes;
use PopupPilot\Content\PostTypes;
use PopupPilot\Frontend\Runtime;
use PopupPilot\Security\Capabilities;

if (!defined('ABSPATH')) {
    exit;
}

final class Plugin
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
    }

    public function boot(): void
    {
        (new Menu())->hooks();
        (new PostTypes())->hooks();
        (new Routes())->hooks();
        (new CampaignRoutes())->hooks();
        (new Events())->hooks();
        (new Runtime())->hooks();
        add_action('plugins_loaded', [$this, 'loadTextDomain']);
    }

    public static function activate(): void
    {
        if (!get_option('popuppilot_version')) {
            add_option('popuppilot_version', POPUPPILOT_VERSION);
        } else {
            update_option('popuppilot_version', POPUPPILOT_VERSION);
        }

        if (!get_option('popuppilot_installed_at')) {
            add_option('popuppilot_installed_at', (string) time());
        }

        Events::installTable();

        $adminRole = get_role('administrator');
        if ($adminRole !== null) {
            foreach (Capabilities::all() as $capability) {
                $adminRole->add_cap($capability);
            }
        }
    }

    public static function deactivate(): void
    {
        // Reserved for future cleanup tasks such as scheduled jobs.
    }

    public function loadTextDomain(): void
    {
        load_plugin_textdomain('popuppilot', false, dirname(plugin_basename(POPUPPILOT_FILE)) . '/languages');
    }
}
