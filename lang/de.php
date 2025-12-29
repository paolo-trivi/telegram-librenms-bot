<?php

return [
    'bot' => [
        'start' => "🤖 *LibreBot v2.0 Online*\n\nWillkommen! Geben Sie /help ein, um verfügbare Befehle zu sehen.",
        'access_denied' => "❌ Zugriff verweigert.",
        'command_not_found' => "❌ Unbekannter Befehl. Geben Sie /help für die Liste ein.",
        'error' => "❌ Ein Fehler ist aufgetreten: %message%",
        'maintenance_mode' => "⚠️ Wartungsmodus aktiviert.",
    ],
    'security' => [
        'unauthorized_chat' => "❌ Nicht autorisierter Chat.",
        'rate_limit' => "⚠️ Ratenlimit überschritten. Bitte warten.",
    ],
    'alerts' => [
        'active_title' => "🔔 *Aktive Alarme*",
        'no_alerts' => "✅ Keine aktiven Alarme.",
        'ack_success' => "✅ Alarm %id% bestätigt.",
        'ack_fail' => "❌ Fehler beim Bestätigen von Alarm %id%.",
    ],
    'commands' => [
        'usage_ack' => "❌ Verwendung: /ack <alert_id> [Notiz]",
        'usage_bulk_ack' => "❌ Verwendung: /bulk_ack <Muster> [Notiz]",
        'usage_escalate' => "❌ Verwendung: /escalate <alert_id> <Grund>",
        'usage_device_status' => "❌ Verwendung: /device_status <device_id>",
        'usage_port_status' => "❌ Verwendung: /port_status <device_id> <port_name>",
        'usage_device_add' => "❌ Verwendung: /device_add <hostname> [community]",
        'usage_device_remove' => "❌ Verwendung: /device_remove <device_id>",
        'usage_device_redetect' => "❌ Verwendung: /device_redetect <device_id>",
        'usage_maintenance' => "❌ Verwendung: /maintenance <device_id> <on/off> [Dauer_Stunden]",
        'usage_performance' => "❌ Verwendung: /performance_report <device_id> [Zeitraum]",
        'usage_ping' => "❌ Verwendung: /ping <host>",
        'usage_trace' => "❌ Verwendung: /trace <host>",
        'usage_mtr' => "❌ Verwendung: /mtr <host> [count]",
        'usage_ns' => "❌ Verwendung: /ns <host>",
        'usage_dig' => "❌ Verwendung: /dig <domain> [record_type]",
        'usage_whois' => "❌ Verwendung: /whois <domain|ip>",
        'usage_port_scan' => "❌ Verwendung: /port_scan <host> [port_range]",
        'usage_ssl_check' => "❌ Verwendung: /ssl_check <host> [port]",
        'usage_http_check' => "❌ Verwendung: /http_check <url>",
        'usage_calc' => "❌ Verwendung: /calc <cidr> (z.B. 192.168.1.0/24)",
        'usage_convert' => "❌ Verwendung: /convert <value> <from> <to>",
        'usage_alert_history' => "❌ Verwendung: /alert_history <device_id>",
        'usage_network_summary' => "❌ Verwendung: /network_summary <host>",
        'unknown_command' => "❌ Unbekannter Befehl: /%command%\nVerwenden Sie /help, um verfügbare Befehle zu sehen.",
    ]
];
