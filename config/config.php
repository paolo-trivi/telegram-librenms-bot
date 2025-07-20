<?php
/**
 * Configurazione LibreBot v2.0
 * Migrata da v1.0 il 2025-07-19 06:06:41
 */

// Configurazione del bot Telegram
$botToken = '719933142:AAHW32hzQ5t0bCkHXj51bchVU3gBGtYgcVk';

// Configurazione di LibreNMS
$librenmsUrl = 'http://192.168.88.6:8000';
$librenmsToken = '07330b9e7f1c5386fc457f7b3211a73d';

// Chat ID e thread autorizzati
$allowedChatIds = array (
  0 => 65822593,
);
$allowedThreads = array (
  0 => NULL,
  1 => 24,
);

// Percorso dei file
$logFile = __DIR__ . '/../logs/bot.log';
$dbFile = __DIR__ . '/../logs/bot.db';

// Configurazioni sicurezza
$security = [
    'rate_limiting' => true,
    'max_commands_per_minute' => 10,
    'max_commands_per_hour' => 60,
    'allowed_shell_commands' => array (
  0 => 'ping',
  1 => 'traceroute',
  2 => 'nslookup',
  3 => 'dig',
  4 => 'mtr',
  5 => 'nmap',
),
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
$userPermissions = array (
    65822593 => 'admin',
);

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
