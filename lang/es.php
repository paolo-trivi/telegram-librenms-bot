<?php

return [
    'bot' => [
        'start' => "🤖 *LibreBot v2.0 En línea*\n\n¡Bienvenido! Escribe /help para ver los comandos disponibles.",
        'access_denied' => "❌ Acceso denegado.",
        'command_not_found' => "❌ Comando desconocido. Escribe /help para la lista.",
        'error' => "❌ Ha ocurrido un error: %message%",
        'maintenance_mode' => "⚠️ Modo mantenimiento activado.",
    ],
    'security' => [
        'unauthorized_chat' => "❌ Chat no autorizado.",
        'rate_limit' => "⚠️ Límite de tasa excedido. Por favor espera.",
    ],
    'alerts' => [
        'active_title' => "🔔 *Alertas Activas*",
        'no_alerts' => "✅ No hay alertas activas.",
        'ack_success' => "✅ Alerta %id% confirmada.",
        'ack_fail' => "❌ Error al confirmar alerta %id%.",
    ],
    'commands' => [
        'usage_ack' => "❌ Uso: /ack <alert_id> [nota]",
        'usage_bulk_ack' => "❌ Uso: /bulk_ack <patrón> [nota]",
        'usage_escalate' => "❌ Uso: /escalate <alert_id> <motivo>",
        'usage_device_status' => "❌ Uso: /device_status <device_id>",
        'usage_port_status' => "❌ Uso: /port_status <device_id> <port_name>",
        'usage_device_add' => "❌ Uso: /device_add <hostname> [community]",
        'usage_device_remove' => "❌ Uso: /device_remove <device_id>",
        'usage_device_redetect' => "❌ Uso: /device_redetect <device_id>",
        'usage_maintenance' => "❌ Uso: /maintenance <device_id> <on/off> [duración_horas]",
        'usage_performance' => "❌ Uso: /performance_report <device_id> [período]",
        'usage_ping' => "❌ Uso: /ping <host>",
        'usage_trace' => "❌ Uso: /trace <host>",
        'usage_mtr' => "❌ Uso: /mtr <host> [count]",
        'usage_ns' => "❌ Uso: /ns <host>",
        'usage_dig' => "❌ Uso: /dig <domain> [record_type]",
        'usage_whois' => "❌ Uso: /whois <domain|ip>",
        'usage_port_scan' => "❌ Uso: /port_scan <host> [port_range]",
        'usage_ssl_check' => "❌ Uso: /ssl_check <host> [port]",
        'usage_http_check' => "❌ Uso: /http_check <url>",
        'usage_calc' => "❌ Uso: /calc <cidr> (ej. 192.168.1.0/24)",
        'usage_convert' => "❌ Uso: /convert <value> <from> <to>",
        'usage_alert_history' => "❌ Uso: /alert_history <device_id>",
        'usage_network_summary' => "❌ Uso: /network_summary <host>",
        'unknown_command' => "❌ Comando desconocido: /%command%\nUsa /help para ver los comandos disponibles.",
    ]
];
