<?php

declare(strict_types=1);

namespace PopupPilot\Analytics;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) {
    exit;
}

final class Events
{
    private const REST_NAMESPACE = 'popuppilot/v1';

    public static function tableName(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'popuppilot_events';
    }

    public static function installTable(): void
    {
        global $wpdb;

        $table = self::tableName();
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            popup_id BIGINT UNSIGNED NOT NULL,
            campaign_id BIGINT UNSIGNED NULL,
            event_type VARCHAR(32) NOT NULL,
            event_value DECIMAL(12,2) NULL,
            page_url TEXT NULL,
            device VARCHAR(16) NULL,
            source VARCHAR(128) NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY popup_id (popup_id),
            KEY campaign_id (campaign_id),
            KEY event_type (event_type),
            KEY created_at (created_at)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function hooks(): void
    {
        add_action('rest_api_init', [$this, 'register']);
    }

    public function register(): void
    {
        register_rest_route(self::REST_NAMESPACE, '/events', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'track'],
                'permission_callback' => '__return_true',
            ],
        ]);

        register_rest_route(self::REST_NAMESPACE, '/analytics/overview', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'overview'],
                'permission_callback' => static fn() => current_user_can('view_popup_analytics'),
            ],
        ]);
    }

    public function track(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        global $wpdb;

        $popupId = (int) $request->get_param('popup_id');
        $eventType = sanitize_key((string) $request->get_param('event_type'));
        $allowed = ['impression', 'view', 'click', 'conversion'];

        if ($popupId <= 0 || !in_array($eventType, $allowed, true)) {
            return new WP_Error('popuppilot_invalid_event', __('Invalid event payload.', 'popuppilot'), ['status' => 400]);
        }

        $wpdb->insert(self::tableName(), [
            'popup_id' => $popupId,
            'campaign_id' => (int) $request->get_param('campaign_id') ?: null,
            'event_type' => $eventType,
            'event_value' => is_numeric($request->get_param('event_value')) ? (float) $request->get_param('event_value') : null,
            'page_url' => esc_url_raw((string) $request->get_param('page_url')),
            'device' => sanitize_text_field((string) $request->get_param('device')),
            'source' => sanitize_text_field((string) $request->get_param('source')),
            'created_at' => gmdate('Y-m-d H:i:s'),
        ], ['%d', '%d', '%s', '%f', '%s', '%s', '%s', '%s']);

        return new WP_REST_Response(['tracked' => true]);
    }

    public function overview(): WP_REST_Response
    {
        global $wpdb;

        $table = self::tableName();
        $impressions = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE event_type='impression'");
        $views = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE event_type='view'");
        $clicks = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE event_type='click'");
        $conversions = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE event_type='conversion'");
        $revenue = (float) $wpdb->get_var("SELECT COALESCE(SUM(event_value),0) FROM {$table} WHERE event_type='conversion'");
        $ctr = $views > 0 ? round(($clicks / $views) * 100, 2) : 0;

        return new WP_REST_Response([
            'impressions' => $impressions,
            'views' => $views,
            'clicks' => $clicks,
            'conversions' => $conversions,
            'ctr' => $ctr,
            'revenue' => $revenue,
        ]);
    }
}
