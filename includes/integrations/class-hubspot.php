<?php

declare(strict_types=1);

namespace PopupPilot\Integrations;

if (!defined('ABSPATH')) {
    exit;
}

final class HubSpot
{
    public function hooks(): void
    {
        add_action('popuppilot_form_submission', [$this, 'sync'], 10, 2);
    }

    public function sync(array $data, int $popupId): void
    {
        $settings = get_option('popuppilot_hubspot_settings', []);
        if (empty($settings['access_token'])) {
            return;
        }

        $email = $data['email'] ?? null;
        if (!$email || !is_email($email)) {
            return;
        }

        $url = "https://api.hubapi.com/crm/v3/objects/contacts";

        wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $settings['access_token'],
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode([
                'properties' => array_filter([
                    'email' => $email,
                    'firstname' => $data['first_name'] ?? null,
                    'lastname' => $data['last_name'] ?? null,
                ]),
            ]),
        ]);
    }
}
