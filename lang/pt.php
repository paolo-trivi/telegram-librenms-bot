<?php

return [
    'bot' => [
        'start' => "🤖 *LibreBot v2.0 Online*\n\nBem-vindo! Digite /help para ver os comandos disponíveis.",
        'access_denied' => "❌ Acesso negado.",
        'command_not_found' => "❌ Comando desconhecido. Digite /help para a lista.",
        'error' => "❌ Ocorreu um erro: %message%",
        'maintenance_mode' => "⚠️ Modo de manutenção ativado.",
    ],
    'security' => [
        'unauthorized_chat' => "❌ Chat não autorizado.",
        'rate_limit' => "⚠️ Limite de taxa excedido. Por favor aguarde.",
    ],
    'alerts' => [
        'active_title' => "🔔 *Alertas Ativos*",
        'no_alerts' => "✅ Nenhum alerta ativo.",
        'ack_success' => "✅ Alerta %id% confirmado.",
        'ack_fail' => "❌ Falha ao confirmar alerta %id%.",
    ],
    'commands' => [
        'usage_ack' => "❌ Uso: /ack <alert_id> [nota]",
        'usage_bulk_ack' => "❌ Uso: /bulk_ack <padrão> [nota]",
        'usage_escalate' => "❌ Uso: /escalate <alert_id> <motivo>",
        'usage_device_status' => "❌ Uso: /device_status <device_id>",
        'usage_port_status' => "❌ Uso: /port_status <device_id> <port_name>",
        'usage_device_add' => "❌ Uso: /device_add <hostname> [community]",
        'usage_device_remove' => "❌ Uso: /device_remove <device_id>",
        'usage_device_redetect' => "❌ Uso: /device_redetect <device_id>",
        'usage_maintenance' => "❌ Uso: /maintenance <device_id> <on/off> [duração_horas]",
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
        'usage_calc' => "❌ Uso: /calc <cidr> (ex. 192.168.1.0/24)",
        'usage_convert' => "❌ Uso: /convert <value> <from> <to>",
        'usage_alert_history' => "❌ Uso: /alert_history <device_id>",
        'usage_network_summary' => "❌ Uso: /network_summary <host>",
        'unknown_command' => "❌ Comando desconhecido: /%command%\nUse /help para ver os comandos disponíveis.",
    ]
];
