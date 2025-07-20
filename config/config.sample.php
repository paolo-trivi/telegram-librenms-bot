<?php
/**
 * Esempio di configurazione LibreBot v2.0
 * Copia questo file in config/config.php e personalizza i valori!
 */

// Configurazione del bot Telegram
$botToken = 'INSERISCI_IL_TUO_TOKEN_TELEGRAM';

// Configurazione di LibreNMS
$librenmsUrl = 'http://librenms.example.net';
$librenmsToken = 'INSERISCI_IL_TUO_TOKEN_LIBRENMS';

// Chat ID e thread autorizzati
$allowedChatIds = [
    12345678, // Sostituisci con i tuoi chat ID autorizzati
];
$allowedThreads = [
    null, // Facoltativo: thread ID per gruppi
];

// Percorso dei file
$logFile = __DIR__ . '/../logs/bot.log';
$dbFile = __DIR__ . '/../logs/bot.db';

// Configurazioni sicurezza
$security = [
    'rate_limiting' => true,
    'max_commands_per_minute' => 10,
    'max_commands_per_hour' => 60,
    'allowed_shell_commands' => [
        'ping', 'traceroute', 'nslookup', 'dig', 'mtr', 'nmap'
    ],
    'ip_whitelist' => ['192.168.0.0/16', '10.0.0.0/8', '172.16.0.0/12'],
    'command_timeout' => 30,
    'max_output_length' => 4000,
    'log_failed_attempts' => true,
    'max_failed_attempts' => 5,
    'ban_duration' => 3600
];

// Ruoli utente
$userRoles = [
    'admin' => [
        'alert_*', 'device_*', 'network_*', 'maintenance_*', 
        'schedule_*', 'bot_*', 'report_*', 'system_*'
    ],
    'operator' => [
        'alert_list', 'alert_ack', 'alert_stats', 'device_list', 
        'device_status', 'network_ping', 'network_trace', 'network_ns',
        'network_mtr', 'network_dig', 'report_daily'
    ],
    'viewer' => [
        'alert_list', 'device_list', 'device_status', 'alert_stats'
    ]
];

// Assegnazione ruoli agli utenti (chat_id => role)
$userPermissions = [
    // 12345678 => 'admin',
];

// Configurazione notifiche
$notifications = [
    'daily_report' => false,
    'daily_report_time' => '08:00',
    'daily_report_timezone' => 'Europe/Rome',
    'alert_escalation' => false,
    'escalation_threshold' => 3600,
    'emergency_contacts' => []
];

// Configurazione avanzata
$advanced = [
    'enable_caching' => true,
    'cache_duration' => 300,
    'enable_web_dashboard' => false,
    'web_dashboard_port' => 8080,
    'enable_plugins' => false,
    'plugin_directory' => __DIR__ . '/../plugins',
    'backup_config' => true,
    'backup_interval' => 86400
];

// Debug e sviluppo
$debug = [
    'enabled' => false,
    'log_level' => 'INFO',
    'verbose_logging' => false
];

// Raggruppa tutta la configurazione
$config = compact('security', 'userRoles', 'userPermissions', 'notifications', 'advanced', 'debug'); 