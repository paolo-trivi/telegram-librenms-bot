<?php

return [
    'bot' => [
        'start' => "🤖 *LibreBot v2.0 Online*\n\nWelcome! Type /help to see available commands.",
        'access_denied' => "❌ Access denied.",
        'command_not_found' => "❌ Unknown command. Type /help for list.",
        'error' => "❌ An error occurred: %message%",
        'maintenance_mode' => "⚠️ Maintenance mode enabled.",
    ],
    'security' => [
        'unauthorized_chat' => "❌ Unauthorized chat.",
        'rate_limit' => "⚠️ Rate limit exceeded. Please wait.",
    ],
    'alerts' => [
        'active_title' => "🔔 *Active Alerts*",
        'no_alerts' => "✅ No active alerts.",
        'ack_success' => "✅ Alert %id% acknowledged.",
        'ack_fail' => "❌ Failed to acknowledge alert %id%.",
    ],
    'commands' => [
        'usage_ack' => "❌ Usage: /ack <alert_id> [note]",
        'usage_bulk_ack' => "❌ Usage: /bulk_ack <pattern> [note]",
        'usage_escalate' => "❌ Usage: /escalate <alert_id> <reason>",
        'usage_device_status' => "❌ Usage: /device_status <device_id>",
        'usage_port_status' => "❌ Usage: /port_status <device_id> <port_name>",
        'usage_device_add' => "❌ Usage: /device_add <hostname> [community]",
        'usage_device_remove' => "❌ Usage: /device_remove <device_id>",
        'usage_device_redetect' => "❌ Usage: /device_redetect <device_id>",
        'usage_maintenance' => "❌ Usage: /maintenance <device_id> <on/off> [duration_hours]",
        'usage_performance' => "❌ Usage: /performance_report <device_id> [period]",
        'usage_ping' => "❌ Usage: /ping <host>",
        'usage_trace' => "❌ Usage: /trace <host>",
        'usage_mtr' => "❌ Usage: /mtr <host> [count]",
        'usage_ns' => "❌ Usage: /ns <host>",
        'usage_dig' => "❌ Usage: /dig <domain> [record_type]",
        'usage_whois' => "❌ Usage: /whois <domain|ip>",
        'usage_port_scan' => "❌ Usage: /port_scan <host> [port_range]",
        'usage_ssl_check' => "❌ Usage: /ssl_check <host> [port]",
        'usage_http_check' => "❌ Usage: /http_check <url>",
        'usage_calc' => "❌ Usage: /calc <cidr> (e.g. 192.168.1.0/24)",
        'usage_convert' => "❌ Usage: /convert <value> <from> <to>",
        'usage_alert_history' => "❌ Usage: /alert_history <device_id>",
        'usage_network_summary' => "❌ Usage: /network_summary <host>",
        'unknown_command' => "❌ Unknown command: /%command%\nUse /help to see available commands.",
    ]
];
