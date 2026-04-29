<?php

declare(strict_types=1);

namespace PopupPilot\Integrations;

if (!defined('ABSPATH')) {
    exit;
}

final class ConvertKit
{
    public function hooks(): void
    {
        add_action('popuppilot_form_submission', [$this, 'sync'], 10, 2);
    }

    public function sync(array $data, int $popupId): void
    {
        $settings = get_option('popuppilot_convertkit_settings', []);
        if (empty($settings['api_key']) || empty($settings['form_id'])) {
            return;
        }

        $email = $data['email'] ?? null;
        if (!$email || !is_email($email)) {
            return;
        }

        $url = "https://api.convertkit.com/v3/forms/{$settings['form_id']}/subscribe";

        wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode([
                'api_key' => $settings['api_key'],
                'email' => $email,
                'first_name' => $data['first_name'] ?? null,
            ]),
        ]);
    }
}
