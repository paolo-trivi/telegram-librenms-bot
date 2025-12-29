<?php
/**
 * LibreBot v2.0 Sample Configuration
 * Copy this file to config/config.php and customize the values!
 */

// Telegram bot configuration
$botToken = 'INSERT_YOUR_TELEGRAM_TOKEN';
$language = 'en'; // Supported languages: en, it, fr, es, de, pt

// LibreNMS configuration
$librenmsUrl = 'http://librenms.example.net';
$librenmsToken = 'INSERT_YOUR_LIBRENMS_TOKEN';

// Authorized chat IDs and threads
$allowedChatIds = [
    12345678, // Replace with your authorized chat IDs
];
$allowedThreads = [
    null, // Optional: thread IDs for groups
];

// File paths
$logFile = __DIR__ . '/../logs/bot.log';
$dbFile = __DIR__ . '/../logs/bot.db';

// Security configuration
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

// User roles
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

// Role assignment to users (chat_id => role)
$userPermissions = [
    // 12345678 => 'admin',
];

// Notification configuration
$notifications = [
    'daily_report' => false,
    'daily_report_time' => '08:00',
    'daily_report_timezone' => 'Europe/Rome',
    'alert_escalation' => false,
    'escalation_threshold' => 3600,
    'emergency_contacts' => []
];

// Advanced configuration
$advanced = [
    'enable_caching' => true,
    'cache_duration' => 300,
    'enable_web_dashboard' => false,
    'web_dashboard_port' => 8080,
    'enable_plugins' => false,
    'plugin_directory' => __DIR__ . '/../plugins',
    'backup_config' => true,
    'backup_interval' => 86400,
    'verify_ssl' => true // Set to false for self-signed certificates
];

// Debug and development
$debug = [
    'enabled' => false,
    'log_level' => 'INFO',
    'verbose_logging' => false
];

// Group all configuration
$config = compact(
    'allowedChatIds', 
    'allowedThreads', 
    'security', 
    'userRoles', 
    'userPermissions', 
    'notifications', 
    'advanced', 
    'debug'
); 