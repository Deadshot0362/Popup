<?php

declare(strict_types=1);

namespace PopupPilot\Frontend;

use WP_Post;

if (!defined('ABSPATH')) {
    exit;
}

final class Runtime
{
    public function hooks(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue(): void
    {
        wp_enqueue_script(
            'popuppilot-runtime',
            POPUPPILOT_URL . 'public/js/popuppilot-runtime.js',
            [],
            POPUPPILOT_VERSION,
            true
        );

        wp_localize_script('popuppilot-runtime', 'PopupPilotRuntime', [
            'popups' => $this->getActivePopups(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getActivePopups(): array
    {
        $query = new \WP_Query([
            'post_type' => 'popuppilot_popup',
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        return array_values(array_filter(array_map(function (WP_Post $post): ?array {
            $documentRaw = get_post_meta($post->ID, '_popuppilot_document', true);
            $document = is_string($documentRaw) ? json_decode($documentRaw, true) : null;

            if (!is_array($document)) {
                return null;
            }

            return [
                'id' => $post->ID,
                'title' => $post->post_title,
                'document' => $document,
                'trigger' => $this->decodeMeta($post->ID, '_popuppilot_trigger'),
                'targeting' => $this->decodeMeta($post->ID, '_popuppilot_targeting'),
                'frequency' => $this->decodeMeta($post->ID, '_popuppilot_frequency'),
                'priority' => (int) get_post_meta($post->ID, '_popuppilot_priority', true),
            ];
        }, $query->posts)));
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeMeta(int $postId, string $metaKey): array
    {
        $raw = get_post_meta($postId, $metaKey, true);
        if (!is_string($raw)) {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
