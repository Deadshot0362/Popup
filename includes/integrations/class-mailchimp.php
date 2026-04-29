<?php

declare(strict_types=1);

namespace PopupPilot\Integrations;

if (!defined('ABSPATH')) {
    exit;
}

final class Mailchimp
{
    public function hooks(): void
    {
        add_action('popuppilot_form_submission', [$this, 'sync'], 10, 2);
    }

    public function sync(array $data, int $popupId): void
    {
        $settings = get_option('popuppilot_mailchimp_settings', []);
        if (empty($settings['api_key']) || empty($settings['list_id'])) {
            return;
        }

        $email = $data['email'] ?? null;
        if (!$email || !is_email($email)) {
            return;
        }

        $datacenter = substr($settings['api_key'], strpos($settings['api_key'], '-') + 1);
        $url = "https://{$datacenter}.api.mailchimp.com/3.0/lists/{$settings['list_id']}/members";

        wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'apikey ' . $settings['api_key'],
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode([
                'email_address' => $email,
                'status' => 'subscribed',
                'merge_fields' => array_filter([
                    'FNAME' => $data['first_name'] ?? null,
                    'LNAME' => $data['last_name'] ?? null,
                ]),
            ]),
        ]);
    }
}
