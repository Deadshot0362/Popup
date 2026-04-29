<?php

declare(strict_types=1);

namespace PopupPilot\Api;

use PopupPilot\Security\Capabilities;
use PopupPilot\Validation\PopupSchemaValidator;
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
        register_rest_route(self::REST_NAMESPACE, '/popups', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'listPopups'],
                'permission_callback' => [$this, 'canManage'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'createPopupDraft'],
                'permission_callback' => [$this, 'canManage'],
            ],
        ]);

        register_rest_route(self::REST_NAMESPACE, '/popups/(?P<id>\\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'getPopup'],
                'permission_callback' => [$this, 'canManage'],
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'updatePopup'],
                'permission_callback' => [$this, 'canManage'],
            ],
        ]);

        register_rest_route(self::REST_NAMESPACE, '/popups/(?P<id>\\d+)/status', [
            [
                'methods' => 'PATCH',
                'callback' => [$this, 'updatePopupStatus'],
                'permission_callback' => [$this, 'canManage'],
            ],
        ]);

        register_rest_route(self::REST_NAMESPACE, '/templates', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'listTemplates'],
                'permission_callback' => [$this, 'canManage'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'saveTemplate'],
                'permission_callback' => [$this, 'canManage'],
            ],
        ]);

        register_rest_route(self::REST_NAMESPACE, '/sync', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'syncExport'],
                'permission_callback' => [$this, 'canManage'],
            ],
        ]);
    }

    public function canManage(WP_REST_Request $request): bool|WP_Error
    {
        if (!current_user_can(Capabilities::MANAGE)) {
            return new WP_Error('popuppilot_forbidden', __('Insufficient permissions.', 'popuppilot'), ['status' => 403]);
        }

        if (in_array($request->get_method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $nonce = $request->get_header('X-WP-Nonce');

            if (!is_string($nonce) || $nonce === '' || !wp_verify_nonce($nonce, 'wp_rest')) {
                return new WP_Error('popuppilot_invalid_nonce', __('Invalid or missing nonce.', 'popuppilot'), ['status' => 401]);
            }
        }

        return true;
    }

    public function createPopupDraft(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $title = sanitize_text_field((string) $request->get_param('title'));
        if ($title === '') {
            $title = __('Untitled Popup', 'popuppilot');
        }

        $postId = wp_insert_post([
            'post_type' => 'popuppilot_popup',
            'post_status' => 'draft',
            'post_title' => $title,
        ], true);

        if (is_wp_error($postId)) {
            return $postId;
        }

        $document = $request->get_param('document');
        if (is_array($document)) {
            $validator = new PopupSchemaValidator();
            $result = $validator->validate($document);

            if (!$result['valid']) {
                return new WP_Error('popuppilot_invalid_document', implode(' ', $result['errors']), ['status' => 400]);
            }

            update_post_meta($postId, '_popuppilot_document', wp_json_encode($document));
        }

        return new WP_REST_Response(['id' => $postId, 'status' => 'draft'], 201);
    }

    public function getPopup(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $post = $this->getPopupPost((int) $request['id']);
        if (is_wp_error($post)) {
            return $post;
        }

        $document = get_post_meta($post->ID, '_popuppilot_document', true);

        return new WP_REST_Response([
            'id' => $post->ID,
            'title' => $post->post_title,
            'status' => $post->post_status,
            'document' => is_string($document) ? json_decode($document, true) : null,
        ]);
    }

    public function updatePopup(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $post = $this->getPopupPost((int) $request['id']);
        if (is_wp_error($post)) {
            return $post;
        }

        $document = $request->get_param('document');
        if (!is_array($document)) {
            return new WP_Error('popuppilot_invalid_document', __('Document must be an object.', 'popuppilot'), ['status' => 400]);
        }

        $trigger = $request->get_param('trigger');
        $targeting = $request->get_param('targeting');
        $frequency = $request->get_param('frequency');
        $priority = $request->get_param('priority');

        $validator = new PopupSchemaValidator();
        $result = $validator->validate($document);
        if (!$result['valid']) {
            return new WP_Error('popuppilot_invalid_document', implode(' ', $result['errors']), ['status' => 400]);
        }

        update_post_meta($post->ID, '_popuppilot_document', wp_json_encode($document));
        if (is_array($trigger)) {
            update_post_meta($post->ID, '_popuppilot_trigger', wp_json_encode($trigger));
        }
        if (is_array($targeting)) {
            update_post_meta($post->ID, '_popuppilot_targeting', wp_json_encode($targeting));
        }
        if (is_array($frequency)) {
            update_post_meta($post->ID, '_popuppilot_frequency', wp_json_encode($frequency));
        }
        if (is_numeric($priority)) {
            update_post_meta($post->ID, '_popuppilot_priority', (int) $priority);
        }

        return new WP_REST_Response(['id' => $post->ID, 'updated' => true]);
    }

    public function updatePopupStatus(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $post = $this->getPopupPost((int) $request['id']);
        if (is_wp_error($post)) {
            return $post;
        }

        $status = sanitize_key((string) $request->get_param('status'));
        $allowed = ['draft', 'publish'];
        if (!in_array($status, $allowed, true)) {
            return new WP_Error('popuppilot_invalid_status', __('Invalid status.', 'popuppilot'), ['status' => 400]);
        }

        $result = wp_update_post([
            'ID' => $post->ID,
            'post_status' => $status,
        ], true);

        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response(['id' => $post->ID, 'status' => $status]);
    }

    public function listPopups(WP_REST_Request $request): WP_REST_Response
    {
        $status = $request->get_param('status');
        $args = [
            'post_type' => 'popuppilot_popup',
            'post_status' => is_string($status) && $status !== '' ? sanitize_key($status) : ['draft', 'publish'],
            'posts_per_page' => 50,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        $query = new \WP_Query($args);
        $items = array_map(static function (WP_Post $post): array {
            return [
                'id' => $post->ID,
                'title' => $post->post_title,
                'status' => $post->post_status,
                'updated_at' => $post->post_modified_gmt,
            ];
        }, $query->posts);

        return new WP_REST_Response(['items' => $items]);
    }

    public function saveTemplate(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $name = sanitize_text_field((string) $request->get_param('name'));
        $document = $request->get_param('document');

        if ($name === '') {
            return new WP_Error('popuppilot_template_name_required', __('Template name is required.', 'popuppilot'), ['status' => 400]);
        }

        if (!is_array($document)) {
            return new WP_Error('popuppilot_template_document_required', __('Template document is required.', 'popuppilot'), ['status' => 400]);
        }

        $templates = get_option('popuppilot_templates', []);
        if (!is_array($templates)) {
            $templates = [];
        }

        $id = 'tpl_' . wp_generate_uuid4();
        $templates[] = [
            'id' => $id,
            'name' => $name,
            'document' => $document,
            'created_at' => gmdate('c'),
        ];

        update_option('popuppilot_templates', $templates, false);

        return new WP_REST_Response(['id' => $id, 'name' => $name], 201);
    }

    public function listTemplates(): WP_REST_Response
    {
        $templates = get_option('popuppilot_templates', []);
        if (!is_array($templates)) {
            $templates = [];
        }

        return new WP_REST_Response(['items' => $templates]);
    }

    public function syncExport(): WP_REST_Response
    {
        $popups = $this->listPopups(new WP_REST_Request())->get_data();
        $templates = $this->listTemplates()->get_data();

        return new WP_REST_Response([
            'popups' => $popups['items'],
            'templates' => $templates['items'],
            'exported_at' => gmdate('c'),
        ]);
    }

    private function getPopupPost(int $postId): WP_Post|WP_Error
    {
        $post = get_post($postId);

        if (!$post instanceof WP_Post || $post->post_type !== 'popuppilot_popup') {
            return new WP_Error('popuppilot_not_found', __('Popup not found.', 'popuppilot'), ['status' => 404]);
        }

        return $post;
    }
}
