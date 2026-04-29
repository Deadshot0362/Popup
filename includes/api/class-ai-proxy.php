<?php

declare(strict_types=1);

namespace PopupPilot\Api;

use PopupPilot\Validation\PopupSchemaValidator;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) {
    exit;
}

final class AIProxy
{
    private const REST_NAMESPACE = 'popuppilot/v1';

    public function hooks(): void
    {
        add_action('rest_api_init', [$this, 'register']);
    }

    public function register(): void
    {
        register_rest_route(self::REST_NAMESPACE, '/ai/generate', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'generate'],
                'permission_callback' => static fn() => current_user_can('manage_popups'),
            ],
        ]);
    }

    public function generate(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $prompt = sanitize_textarea_field((string) $request->get_param('prompt'));
        $context = $request->get_param('context'); // Optional current document state

        $apiKey = get_option('popuppilot_openai_key');
        if (empty($apiKey)) {
            return new WP_Error('popuppilot_no_ai_key', __('OpenAI API key missing.', 'popuppilot'), ['status' => 400]);
        }

        $systemPrompt = "You are a UI assistant. Generate a JSON popup document following this schema: { version, meta: { name }, steps: [ { id, components: [ { type, x, y, width, height, props: {} } ] } ] }. Allowed types: text, image, button, form, countdown, video.";

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode([
                'model' => 'gpt-4o',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $prompt . (is_array($context) ? "\nContext: " . wp_json_encode($context) : "")],
                ],
                'response_format' => ['type' => 'json_object'],
            ]),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $content = $body['choices'][0]['message']['content'] ?? '{}';
        $doc = json_decode($content, true);

        if (!is_array($doc)) {
            return new WP_Error('popuppilot_ai_invalid_json', __('AI returned invalid JSON.', 'popuppilot'), ['status' => 500]);
        }

        $validator = new PopupSchemaValidator();
        $result = $validator->validate($doc);
        if (!$result['valid']) {
            return new WP_Error('popuppilot_ai_schema_fail', implode(' ', $result['errors']), ['status' => 500]);
        }

        return new WP_REST_Response(['document' => $doc]);
    }
}
