<?php

declare(strict_types=1);

namespace PopupPilot\Security;

if (!defined('ABSPATH')) {
    exit;
}

final class Capabilities
{
    public const MANAGE = 'manage_popups';
    public const VIEW_ANALYTICS = 'view_popup_analytics';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::MANAGE,
            self::VIEW_ANALYTICS,
        ];
    }
}
