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

    public function renderDashboard(): void
    {
        echo '<div class="wrap"><h1>' . esc_html__('PopupPilot Dashboard', 'popuppilot') . '</h1></div>';
    }

    public function renderPopups(): void
    {
        echo '<div class="wrap"><h1>' . esc_html__('PopupPilot Popups', 'popuppilot') . '</h1></div>';
    }

    public function renderCampaigns(): void
    {
        echo '<div class="wrap"><h1>' . esc_html__('PopupPilot Campaigns', 'popuppilot') . '</h1></div>';
    }

    public function renderIntegrations(): void
    {
        echo '<div class="wrap"><h1>' . esc_html__('PopupPilot Integrations', 'popuppilot') . '</h1></div>';
    }

    public function renderSettings(): void
    {
        echo '<div class="wrap"><h1>' . esc_html__('PopupPilot Settings', 'popuppilot') . '</h1></div>';
    }
}
