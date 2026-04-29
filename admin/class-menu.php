<?php

declare(strict_types=1);

namespace PopupPilot\Admin;

use PopupPilot\Security\Capabilities;

if (!defined('ABSPATH')) {
    exit;
}

final class Menu
{
    public function hooks(): void
    {
        add_action('admin_menu', [$this, 'register']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    }

    public function register(): void
    {
        $capability = Capabilities::MANAGE;
        $slug = 'popuppilot-dashboard';

        add_menu_page(
            __('PopupPilot', 'popuppilot'),
            __('PopupPilot', 'popuppilot'),
            $capability,
            $slug,
            [$this, 'renderDashboard'],
            'dashicons-format-image',
            58
        );

        add_submenu_page($slug, __('Dashboard', 'popuppilot'), __('Dashboard', 'popuppilot'), $capability, $slug, [$this, 'renderDashboard']);
        add_submenu_page($slug, __('Popups', 'popuppilot'), __('Popups', 'popuppilot'), $capability, 'popuppilot-popups', [$this, 'renderPopups']);
        add_submenu_page($slug, __('Campaigns', 'popuppilot'), __('Campaigns', 'popuppilot'), $capability, 'popuppilot-campaigns', [$this, 'renderCampaigns']);
        add_submenu_page($slug, __('Integrations', 'popuppilot'), __('Integrations', 'popuppilot'), $capability, 'popuppilot-integrations', [$this, 'renderIntegrations']);
        add_submenu_page($slug, __('Settings', 'popuppilot'), __('Settings', 'popuppilot'), $capability, 'popuppilot-settings', [$this, 'renderSettings']);
    }

    public function enqueue(string $hook): void
    {
        if (strpos($hook, 'popuppilot') === false) {
            return;
        }

        wp_enqueue_style('popuppilot-admin', POPUPPILOT_URL . 'admin/editor/dist/assets/index-DSwkQUxG.css', [], POPUPPILOT_VERSION);
        wp_enqueue_script('popuppilot-admin', POPUPPILOT_URL . 'admin/editor/dist/assets/index-Cg6R01hc.js', [], POPUPPILOT_VERSION, true);

        wp_localize_script('popuppilot-admin', 'wpApiSettings', [
            'root' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
    }

    public function renderDashboard(): void
    {
        echo '<div id="root"></div>';
    }

    public function renderPopups(): void
    {
        echo '<div id="root"></div>';
    }

    public function renderCampaigns(): void
    {
        echo '<div id="root"></div>';
    }

    public function renderIntegrations(): void
    {
        echo '<div id="root"></div>';
    }

    public function renderSettings(): void
    {
        echo '<div id="root"></div>';
    }
}
