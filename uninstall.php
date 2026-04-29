<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('popuppilot_version');
delete_option('popuppilot_installed_at');
delete_option('popuppilot_templates');
