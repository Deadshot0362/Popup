<?php

declare(strict_types=1);

namespace PopupPilot\Integrations;

if (!defined('ABSPATH')) {
    exit;
}

final class Webhooks
{
    public function hooks(): void
    {
        add_action('popuppilot_form_submission', [$this, 'enqueue'], 10, 2);
        add_action('popuppilot_webhook_delivery', [$this, 'deliver'], 10, 1);
    }

    public function enqueue(array $data, int $popupId): void
    {
        $webhooks = get_option('popuppilot_webhooks', []);
        if (!is_array($webhooks)) {
            return;
        }

        foreach ($webhooks as $webhook) {
            if (empty($webhook['url'])) {
                continue;
            }

            wp_schedule_single_event(time(), 'popuppilot_webhook_delivery', [
                [
                    'url' => $webhook['url'],
                    'payload' => [
                        'data' => $data,
                        'popup_id' => $popupId,
                        'timestamp' => time(),
                    ],
                    'retries' => 0,
                ],
            ]);
        }
    }

    public function deliver(array $job): void
    {
        $response = wp_remote_post($job['url'], [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => wp_json_encode($job['payload']),
            'timeout' => 15,
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) >= 400) {
            if ($job['retries'] < 3) {
                $job['retries']++;
                wp_schedule_single_event(time() + (60 * pow(2, $job['retries'])), 'popuppilot_webhook_delivery', [$job]);
            }
        }
    }
}
