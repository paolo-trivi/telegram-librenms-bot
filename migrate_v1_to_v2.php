<?php
/**
 * Migration Script v1.0 → v2.0
 * 
 * Questo script aiuta nella migrazione dalla versione 1.0 alla 2.0
 */

echo "🔄 LibreBot Migration: v1.0 → v2.0\n";
echo "==================================\n\n";

// Controlla se esiste configurazione v1
$v1Config = 'config.php';
$v1ConfigSample = 'config.sample.php';

if (!file_exists($v1Config) && !file_exists($v1ConfigSample)) {
    echo "❌ Nessuna configurazione v1.0 trovata.\n";
    echo "Se è una nuova installazione, usa: php install.php\n";
    exit(1);
}

echo "📋 Configurazione v1.0 trovata. Inizio migrazione...\n\n";

// Leggi configurazione v1
if (file_exists($v1Config)) {
    echo "📖 Lettura configurazione da $v1Config...\n";
    include $v1Config;
} else {
    echo "📖 Lettura configurazione template da $v1ConfigSample...\n";
    include $v1ConfigSample;
    echo "⚠️ Configurazione template caricata. Dovrai inserire i valori reali.\n";
}

// Backup configurazione v1
$backupFile = 'config_v1_backup_' . date('Y-m-d_H-i-s') . '.php';
if (file_exists($v1Config)) {
    copy($v1Config, $backupFile);
    echo "💾 Backup creato: $backupFile\n";
}

// Verifica variabili v1
$missingVars = [];
$requiredVars = ['botToken', 'librenmsUrl', 'librenmsToken', 'allowedChatIds'];

foreach ($requiredVars as $var) {
    if (!isset($$var) || (is_array($$var) && empty($$var)) || (is_string($$var) && empty($$var))) {
        $missingVars[] = $var;
    }
}

if (!empty($missingVars)) {
    echo "⚠️ Variabili mancanti nella configurazione v1:\n";
    foreach ($missingVars as $var) {
        echo "  - \$$var\n";
    }
    echo "\nCompleta la configurazione manualmente dopo la migrazione.\n\n";
}

// Crea directory v2
$directories = ['config', 'logs', 'lib', 'commands'];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "📁 Creata directory: $dir\n";
    }
}

// Configura valori predefiniti per v2
$v2BotToken = $botToken ?? '';
$v2LibrenmsUrl = $librenmsUrl ?? '';
$v2LibrenmsToken = $librenmsToken ?? '';
$v2AllowedChatIds = $allowedChatIds ?? [];
$v2AllowedThreads = $allowedThreads ?? [];

// Chiedi configurazioni aggiuntive
echo "\n⚙️ Configurazione aggiuntiva per v2.0:\n";

echo "Abilita rate limiting? [Y/n]: ";
$rateLimiting = trim(fgets(STDIN));
$enableRateLimit = strtolower($rateLimiting) !== 'n';

echo "Comandi per minuto (default 10): ";
$commandsPerMinute = trim(fgets(STDIN));
$commandsPerMinute = empty($commandsPerMinute) ? 10 : intval($commandsPerMinute);

echo "Vuoi configurare ruoli utente specifici? [y/N]: ";
$configureRoles = trim(fgets(STDIN));

$userPermissions = [];
if (strtolower($configureRoles) === 'y') {
    foreach ($v2AllowedChatIds as $chatId) {
        echo "Ruolo per chat ID $chatId (admin/operator/viewer) [admin]: ";
        $role = trim(fgets(STDIN));
        $userPermissions[$chatId] = empty($role) ? 'admin' : $role;
    }
}

// Controlla comandi disponibili
echo "\n🛠️ Controllo comandi di sistema...\n";
$commands = ['ping', 'traceroute', 'nslookup', 'dig', 'whois', 'mtr', 'nmap'];
$availableCommands = [];

foreach ($commands as $cmd) {
    if (shell_exec("which $cmd 2>/dev/null")) {
        echo "✅ $cmd disponibile\n";
        $availableCommands[] = $cmd;
    } else {
        echo "⚠️ $cmd non trovato\n";
    }
}

// Genera configurazione v2
$v2ConfigContent = '<?php
/**
 * Configurazione LibreBot v2.0
 * Migrata da v1.0 il ' . date('Y-m-d H:i:s') . '
 */

// Configurazione del bot Telegram
$botToken = ' . var_export($v2BotToken, true) . ';

// Configurazione di LibreNMS
$librenmsUrl = ' . var_export($v2LibrenmsUrl, true) . ';
$librenmsToken = ' . var_export($v2LibrenmsToken, true) . ';

// Chat ID e thread autorizzati
$allowedChatIds = ' . var_export($v2AllowedChatIds, true) . ';
$allowedThreads = ' . var_export($v2AllowedThreads, true) . ';

// Percorso dei file
$logFile = __DIR__ . \'/../logs/bot.log\';
$dbFile = __DIR__ . \'/../logs/bot.db\';

// Configurazioni sicurezza
$security = [
    \'rate_limiting\' => ' . var_export($enableRateLimit, true) . ',
    \'max_commands_per_minute\' => ' . $commandsPerMinute . ',
    \'max_commands_per_hour\' => ' . ($commandsPerMinute * 6) . ',
    \'allowed_shell_commands\' => ' . var_export($availableCommands, true) . ',
    \'ip_whitelist\' => [\'192.168.0.0/16\', \'10.0.0.0/8\', \'172.16.0.0/12\'],
    \'command_timeout\' => 30,
    \'max_output_length\' => 4000,
    \'log_failed_attempts\' => true,
    \'max_failed_attempts\' => 5,
    \'ban_duration\' => 3600
];

// Ruoli utente
$userRoles = [
    \'admin\' => [
        \'alert_*\', \'device_*\', \'network_*\', \'maintenance_*\', 
        \'schedule_*\', \'bot_*\', \'report_*\', \'system_*\'
    ],
    \'operator\' => [
        \'alert_list\', \'alert_ack\', \'alert_stats\', \'device_list\', 
        \'device_status\', \'network_ping\', \'network_trace\', \'network_ns\',
        \'network_mtr\', \'network_dig\', \'report_daily\'
    ],
    \'viewer\' => [
        \'alert_list\', \'device_list\', \'device_status\', \'alert_stats\'
    ]
];

// Assegnazione ruoli agli utenti (chat_id => role)
$userPermissions = ' . var_export($userPermissions, true) . ';

// Configurazione notifiche
$notifications = [
    \'daily_report\' => false,
    \'daily_report_time\' => \'08:00\',
    \'daily_report_timezone\' => \'Europe/Rome\',
    \'alert_escalation\' => false,
    \'escalation_threshold\' => 3600,
    \'emergency_contacts\' => []
];

// Configurazione avanzata
$advanced = [
    \'enable_caching\' => true,
    \'cache_duration\' => 300,
    \'enable_web_dashboard\' => false,
    \'web_dashboard_port\' => 8080,
    \'enable_plugins\' => false,
    \'plugin_directory\' => __DIR__ . \'/../plugins\',
    \'backup_config\' => true,
    \'backup_interval\' => 86400
];

// Debug e sviluppo
$debug = [
    \'enabled\' => false,
    \'log_level\' => \'INFO\',
    \'verbose_logging\' => false
];

// Raggruppa tutta la configurazione
$config = compact(\'security\', \'userRoles\', \'userPermissions\', \'notifications\', \'advanced\', \'debug\');
';

// Scrivi configurazione v2
$v2ConfigFile = 'config/config.php';
file_put_contents($v2ConfigFile, $v2ConfigContent);
echo "\n✅ Configurazione v2.0 salvata in: $v2ConfigFile\n";

// Migra log esistenti se presenti
$v1LogFile = 'bot.log';
if (file_exists($v1LogFile)) {
    $v2LogFile = 'logs/bot.log';
    
    echo "📋 Migrazione log esistenti...\n";
    
    // Leggi log v1 e converte in formato v2
    $v1Logs = file_get_contents($v1LogFile);
    $v1Lines = explode("\n", trim($v1Logs));
    
    $v2Logs = [];
    foreach ($v1Lines as $line) {
        if (empty($line)) continue;
        
        // Converte formato v1 in v2 (strutturato)
        if (preg_match('/^\[([^\]]+)\]\s+(.+)$/', $line, $matches)) {
            $timestamp = $matches[1];
            $message = $matches[2];
            
            $v2LogEntry = [
                'timestamp' => $timestamp,
                'level' => 'INFO',
                'pid' => getmypid(),
                'message' => '[MIGRATED] ' . $message
            ];
            
            $v2Logs[] = json_encode($v2LogEntry, JSON_UNESCAPED_UNICODE);
        }
    }
    
    if (!empty($v2Logs)) {
        file_put_contents($v2LogFile, implode("\n", $v2Logs) . "\n");
        echo "✅ " . count($v2Logs) . " log entries migrati\n";
        
        // Backup log v1
        $logBackup = 'bot_v1_backup_' . date('Y-m-d_H-i-s') . '.log';
        copy($v1LogFile, $logBackup);
        echo "💾 Backup log v1: $logBackup\n";
    }
}

// Test configurazione migrata
echo "\n🧪 Test configurazione migrata...\n";

if (!empty($v2BotToken)) {
    echo "Test Telegram API...";
    $telegramTest = @file_get_contents("https://api.telegram.org/bot$v2BotToken/getMe");
    if ($telegramTest !== false) {
        $testResult = json_decode($telegramTest, true);
        if ($testResult['ok'] ?? false) {
            echo " ✅ OK\n";
        } else {
            echo " ❌ Token non valido\n";
        }
    } else {
        echo " ❌ Connessione fallita\n";
    }
}

if (!empty($v2LibrenmsUrl) && !empty($v2LibrenmsToken)) {
    echo "Test LibreNMS API...";
    $ch = curl_init("$v2LibrenmsUrl/api/v0/system");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Auth-Token: $v2LibrenmsToken"]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        echo " ✅ OK\n";
    } else {
        echo " ❌ Errore (HTTP $httpCode)\n";
    }
}

echo "\n🎉 Migrazione completata con successo!\n\n";

echo "📋 Prossimi passi:\n";
echo "1. Verifica la configurazione in: config/config.php\n";
echo "2. Ferma il bot v1.0 se è in esecuzione\n";
echo "3. Avvia il bot v2.0 con: php bot_v2.php\n";
echo "4. Testa le nuove funzionalità con /help\n";
echo "5. Controlla i log in: logs/bot.log\n\n";

echo "🆕 Nuovi comandi disponibili:\n";
echo "   /alert_stats, /device_status, /mtr, /dig, /whois\n";
echo "   /port_scan, /ssl_check, /bot_status, /health\n\n";

echo "🔒 Sicurezza migliorata:\n";
echo "   - Rate limiting: " . ($enableRateLimit ? 'Attivo' : 'Disattivo') . "\n";
echo "   - Sistema ruoli: " . (empty($userPermissions) ? 'Disattivo (legacy mode)' : 'Attivo') . "\n";
echo "   - Logging strutturato: Attivo\n\n";

if (!empty($missingVars)) {
    echo "⚠️ IMPORTANTE: Completa la configurazione mancante in config/config.php:\n";
    foreach ($missingVars as $var) {
        echo "   - \$$var\n";
    }
    echo "\n";
}

echo "📖 Documentazione v2.0: README_v2.md\n";
echo "✨ Buon monitoraggio con LibreBot v2.0!\n";