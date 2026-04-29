<?php

declare(strict_types=1);

namespace PopupPilot\Bootstrap;

if (!defined('ABSPATH')) {
    exit;
}

final class Env
{
    /**
     * @return array<string, mixed>
     */
    public static function config(): array
    {
        return [
            'environment' => POPUPPILOT_ENV,
            'debug' => defined('WP_DEBUG') && WP_DEBUG,
            'version' => POPUPPILOT_VERSION,
            'plugin_path' => POPUPPILOT_PATH,
            'plugin_url' => POPUPPILOT_URL,
        ];
    }
}
