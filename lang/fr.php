<?php

return [
    'bot' => [
        'start' => "🤖 *LibreBot v2.0 En ligne*\n\nBienvenue! Tapez /help pour voir les commandes disponibles.",
        'access_denied' => "❌ Accès refusé.",
        'command_not_found' => "❌ Commande inconnue. Tapez /help pour la liste.",
        'error' => "❌ Une erreur s'est produite: %message%",
        'maintenance_mode' => "⚠️ Mode maintenance activé.",
    ],
    'security' => [
        'unauthorized_chat' => "❌ Chat non autorisé.",
        'rate_limit' => "⚠️ Limite de taux dépassée. Veuillez patienter.",
    ],
    'alerts' => [
        'active_title' => "🔔 *Alertes Actives*",
        'no_alerts' => "✅ Aucune alerte active.",
        'ack_success' => "✅ Alerte %id% acquittée.",
        'ack_fail' => "❌ Échec de l'acquittement de l'alerte %id%.",
    ],
    'commands' => [
        'usage_ack' => "❌ Usage: /ack <alert_id> [note]",
        'usage_bulk_ack' => "❌ Usage: /bulk_ack <pattern> [note]",
        'usage_escalate' => "❌ Usage: /escalate <alert_id> <raison>",
        'usage_device_status' => "❌ Usage: /device_status <device_id>",
        'usage_port_status' => "❌ Usage: /port_status <device_id> <port_name>",
        'usage_device_add' => "❌ Usage: /device_add <hostname> [community]",
        'usage_device_remove' => "❌ Usage: /device_remove <device_id>",
        'usage_device_redetect' => "❌ Usage: /device_redetect <device_id>",
        'usage_maintenance' => "❌ Usage: /maintenance <device_id> <on/off> [durée_heures]",
        'usage_performance' => "❌ Usage: /performance_report <device_id> [période]",
        'usage_ping' => "❌ Usage: /ping <host>",
        'usage_trace' => "❌ Usage: /trace <host>",
        'usage_mtr' => "❌ Usage: /mtr <host> [count]",
        'usage_ns' => "❌ Usage: /ns <host>",
        'usage_dig' => "❌ Usage: /dig <domain> [record_type]",
        'usage_whois' => "❌ Usage: /whois <domain|ip>",
        'usage_port_scan' => "❌ Usage: /port_scan <host> [port_range]",
        'usage_ssl_check' => "❌ Usage: /ssl_check <host> [port]",
        'usage_http_check' => "❌ Usage: /http_check <url>",
        'usage_calc' => "❌ Usage: /calc <cidr> (ex. 192.168.1.0/24)",
        'usage_convert' => "❌ Usage: /convert <value> <from> <to>",
        'usage_alert_history' => "❌ Usage: /alert_history <device_id>",
        'usage_network_summary' => "❌ Usage: /network_summary <host>",
        'unknown_command' => "❌ Commande inconnue: /%command%\nUtilisez /help pour voir les commandes disponibles.",
    ]
];
