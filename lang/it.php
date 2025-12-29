<?php

return [
    'bot' => [
        'start' => "🤖 *LibreBot v2.0 Online*\n\nBenvenuto! Digita /help per i comandi disponibili.",
        'access_denied' => "❌ Accesso negato.",
        'command_not_found' => "❌ Comando sconosciuto. Digita /help per la lista.",
        'error' => "❌ Si è verificato un errore: %message%",
        'maintenance_mode' => "⚠️ Modalità manutenzione attiva.",
    ],
    'security' => [
        'unauthorized_chat' => "❌ Chat non autorizzata.",
        'rate_limit' => "⚠️ Limite richieste superato. Attendi prego.",
    ],
    'alerts' => [
        'active_title' => "🔔 *Alert Attivi*",
        'no_alerts' => "✅ Nessun alert attivo.",
        'ack_success' => "✅ Alert %id% preso in carico.",
        'ack_fail' => "❌ Impossibile gestire alert %id%.",
    ],
    'commands' => [
        'usage_ack' => "❌ Uso: /ack <alert_id> [nota]",
        'usage_bulk_ack' => "❌ Uso: /bulk_ack <pattern> [nota]",
        'usage_escalate' => "❌ Uso: /escalate <alert_id> <motivo>",
        'usage_device_status' => "❌ Uso: /device_status <device_id>",
        'usage_port_status' => "❌ Uso: /port_status <device_id> <port_name>",
        'usage_device_add' => "❌ Uso: /device_add <hostname> [community]",
        'usage_device_remove' => "❌ Uso: /device_remove <device_id>",
        'usage_device_redetect' => "❌ Uso: /device_redetect <device_id>",
        'usage_maintenance' => "❌ Uso: /maintenance <device_id> <on/off> [durata_ore]",
        'usage_performance' => "❌ Uso: /performance_report <device_id> [periodo]",
        'usage_ping' => "❌ Uso: /ping <host>",
        'usage_trace' => "❌ Uso: /trace <host>",
        'usage_mtr' => "❌ Uso: /mtr <host> [count]",
        'usage_ns' => "❌ Uso: /ns <host>",
        'usage_dig' => "❌ Uso: /dig <domain> [record_type]",
        'usage_whois' => "❌ Uso: /whois <domain|ip>",
        'usage_port_scan' => "❌ Uso: /port_scan <host> [port_range]",
        'usage_ssl_check' => "❌ Uso: /ssl_check <host> [port]",
        'usage_http_check' => "❌ Uso: /http_check <url>",
        'usage_calc' => "❌ Uso: /calc <cidr> (es. 192.168.1.0/24)",
        'usage_convert' => "❌ Uso: /convert <value> <from> <to>",
        'usage_alert_history' => "❌ Uso: /alert_history <device_id>",
        'usage_network_summary' => "❌ Uso: /network_summary <host>",
        'unknown_command' => "❌ Comando sconosciuto: /%command%\nUsa /help per vedere i comandi disponibili.",
    ]
];
