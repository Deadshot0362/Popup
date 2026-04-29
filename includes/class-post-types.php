<?php

declare(strict_types=1);

namespace PopupPilot\Content;

if (!defined('ABSPATH')) {
    exit;
}

final class PostTypes
{
    public function hooks(): void
    {
        add_action('init', [$this, 'register']);
    }

    public function register(): void
    {
        register_post_type('popuppilot_popup', [
            'labels' => [
                'name' => __('Popups', 'popuppilot'),
                'singular_name' => __('Popup', 'popuppilot'),
            ],
            'public' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'supports' => ['title'],
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ]);

        register_post_type('popuppilot_campaign', [
            'labels' => [
                'name' => __('Campaigns', 'popuppilot'),
                'singular_name' => __('Campaign', 'popuppilot'),
            ],
            'public' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'supports' => ['title'],
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ]);
    }
}
