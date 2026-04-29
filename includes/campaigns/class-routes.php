<?php

declare(strict_types=1);

namespace PopupPilot\Campaigns;

use WP_Error;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) {
    exit;
}

final class Routes
{
    private const REST_NAMESPACE = 'popuppilot/v1';

    public function hooks(): void
    {
        add_action('rest_api_init', [$this, 'register']);
    }

    public function register(): void
    {
        register_rest_route(self::REST_NAMESPACE, '/campaigns', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'listCampaigns'],
                'permission_callback' => static fn() => current_user_can('manage_popups'),
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'createCampaign'],
                'permission_callback' => static fn() => current_user_can('manage_popups'),
            ],
        ]);

        register_rest_route(self::REST_NAMESPACE, '/campaigns/(?P<id>\\d+)', [
            [
                'methods' => 'PUT',
                'callback' => [$this, 'updateCampaign'],
                'permission_callback' => static fn() => current_user_can('manage_popups'),
            ],
        ]);
    }

    public function createCampaign(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $title = sanitize_text_field((string) $request->get_param('title'));
        $postId = wp_insert_post([
            'post_type' => 'popuppilot_campaign',
            'post_status' => 'draft',
            'post_title' => $title !== '' ? $title : __('Untitled Campaign', 'popuppilot'),
        ], true);

        if (is_wp_error($postId)) {
            return $postId;
        }

        $this->storeCampaignMeta($postId, $request);

        return new WP_REST_Response(['id' => $postId], 201);
    }

    public function updateCampaign(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $id = (int) $request['id'];
        $post = get_post($id);
        if (!$post instanceof WP_Post || $post->post_type !== 'popuppilot_campaign') {
            return new WP_Error('popuppilot_campaign_not_found', __('Campaign not found.', 'popuppilot'), ['status' => 404]);
        }

        $this->storeCampaignMeta($id, $request);

        return new WP_REST_Response(['id' => $id, 'updated' => true]);
    }

    public function listCampaigns(): WP_REST_Response
    {
        $query = new \WP_Query([
            'post_type' => 'popuppilot_campaign',
            'post_status' => ['draft', 'publish'],
            'posts_per_page' => 100,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        $items = array_map(function (WP_Post $post): array {
            return [
                'id' => $post->ID,
                'title' => $post->post_title,
                'status' => get_post_meta($post->ID, '_popuppilot_campaign_status', true) ?: 'draft',
                'schedule' => $this->decodeMeta($post->ID, '_popuppilot_campaign_schedule'),
                'priority' => (int) get_post_meta($post->ID, '_popuppilot_campaign_priority', true),
                'popup_ids' => $this->decodeMeta($post->ID, '_popuppilot_campaign_popup_ids'),
                'variants' => $this->decodeMeta($post->ID, '_popuppilot_campaign_variants'),
            ];
        }, $query->posts);

        return new WP_REST_Response(['items' => $items]);
    }

    private function storeCampaignMeta(int $postId, WP_REST_Request $request): void
    {
        $status = sanitize_key((string) $request->get_param('status'));
        if (in_array($status, ['draft', 'active', 'paused'], true)) {
            update_post_meta($postId, '_popuppilot_campaign_status', $status);
        }

        $schedule = $request->get_param('schedule');
        if (is_array($schedule)) {
            update_post_meta($postId, '_popuppilot_campaign_schedule', wp_json_encode($schedule));
        }

        $priority = $request->get_param('priority');
        if (is_numeric($priority)) {
            update_post_meta($postId, '_popuppilot_campaign_priority', (int) $priority);
        }

        $popupIds = $request->get_param('popup_ids');
        if (is_array($popupIds)) {
            update_post_meta($postId, '_popuppilot_campaign_popup_ids', wp_json_encode(array_values(array_map('intval', $popupIds))));
        }

        $variants = $request->get_param('variants');
        if (is_array($variants)) {
            update_post_meta($postId, '_popuppilot_campaign_variants', wp_json_encode($variants));
        }
    }

    /**
     * @return array<int|string, mixed>
     */
    private function decodeMeta(int $postId, string $key): array
    {
        $raw = get_post_meta($postId, $key, true);
        if (!is_string($raw)) {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
