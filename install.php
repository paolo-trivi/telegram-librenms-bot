<?php
/**
 * LibreBot v2.0 - Installation Script
 * 
 * Questo script aiuta nell'installazione e configurazione di LibreBot v2.0
 */

echo "🤖 LibreBot v2.0 - Installation Script\n";
echo "=====================================\n\n";

// Controlli prerequisiti
echo "🔍 Controllo prerequisiti...\n";

$requirements = [
    'php' => [
        'check' => version_compare(PHP_VERSION, '8.0.0', '>='),
        'message' => 'PHP 8.0+ richiesto (attuale: ' . PHP_VERSION . ')'
    ],
    'curl' => [
        'check' => extension_loaded('curl'),
        'message' => 'Estensione cURL richiesta'
    ],
    'pdo_sqlite' => [
        'check' => extension_loaded('pdo_sqlite'),
        'message' => 'Estensione PDO SQLite richiesta'
    ],
    'json' => [
        'check' => extension_loaded('json'),
        'message' => 'Estensione JSON richiesta'
    ]
];

$allPassed = true;
foreach ($requirements as $name => $req) {
    if ($req['check']) {
        echo "✅ {$req['message']}\n";
    } else {
        echo "❌ {$req['message']}\n";
        $allPassed = false;
    }
}

if (!$allPassed) {
    echo "\n❌ Alcuni prerequisiti non sono soddisfatti. Installali e riprova.\n";
    exit(1);
}

echo "\n✅ Tutti i prerequisiti soddisfatti!\n\n";

// Controllo comandi esterni
echo "🛠️ Controllo comandi esterni...\n";
$commands = ['ping', 'traceroute', 'nslookup', 'dig', 'whois', 'mtr', 'nmap'];
$availableCommands = [];

foreach ($commands as $cmd) {
    if (shell_exec("which $cmd 2>/dev/null")) {
        echo "✅ $cmd disponibile\n";
        $availableCommands[] = $cmd;
    } else {
        echo "⚠️ $cmd non trovato (opzionale)\n";
    }
}

if (empty($availableCommands)) {
    echo "⚠️ Nessun comando di rete trovato. Installa almeno ping e traceroute.\n";
}

echo "\n";

// Controllo configurazione esistente
$configFile = 'config/config.php';
$hasExistingConfig = file_exists($configFile);

if ($hasExistingConfig) {
    echo "📋 Configurazione esistente trovata.\n";
    echo "Vuoi aggiornarla? [y/N]: ";
    $update = trim(fgets(STDIN));
    
    if (strtolower($update) !== 'y') {
        echo "✅ Installazione completata. Usa la configurazione esistente.\n";
        exit(0);
    }
}

// Configurazione interattiva
echo "⚙️ Configurazione LibreBot v2.0\n";
echo "================================\n\n";

echo "📱 Configurazione Telegram:\n";
echo "Token bot (da @BotFather): ";
$botToken = trim(fgets(STDIN));

echo "Chat ID autorizzati (separati da virgola): ";
$chatIds = trim(fgets(STDIN));
$allowedChatIds = array_map('intval', explode(',', $chatIds));

echo "Thread ID autorizzati (separati da virgola, vuoto per nessuno): ";
$threadIds = trim(fgets(STDIN));
$allowedThreads = empty($threadIds) ? [] : array_map('intval', explode(',', $threadIds));

echo "\n🌐 Configurazione LibreNMS:\n";
echo "URL LibreNMS (es. https://librenms.example.com): ";
$librenmsUrl = trim(fgets(STDIN));

echo "Token API LibreNMS: ";
$librenmsToken = trim(fgets(STDIN));

echo "\n🔒 Configurazione sicurezza:\n";
echo "Abilita rate limiting? [Y/n]: ";
$rateLimiting = trim(fgets(STDIN));
$enableRateLimit = strtolower($rateLimiting) !== 'n';

echo "Comandi per minuto (default 10): ";
$commandsPerMinute = trim(fgets(STDIN));
$commandsPerMinute = empty($commandsPerMinute) ? 10 : intval($commandsPerMinute);

echo "IP whitelist CIDR (separati da virgola, default private networks): ";
$ipWhitelist = trim(fgets(STDIN));
if (empty($ipWhitelist)) {
    $ipWhitelist = ['192.168.0.0/16', '10.0.0.0/8', '172.16.0.0/12'];
} else {
    $ipWhitelist = array_map('trim', explode(',', $ipWhitelist));
}

echo "\n👤 Configurazione ruoli utente:\n";
echo "Vuoi configurare ruoli specifici? [y/N]: ";
$configureRoles = trim(fgets(STDIN));

$userPermissions = [];
if (strtolower($configureRoles) === 'y') {
    foreach ($allowedChatIds as $chatId) {
        echo "Ruolo per chat ID $chatId (admin/operator/viewer) [admin]: ";
        $role = trim(fgets(STDIN));
        $userPermissions[$chatId] = empty($role) ? 'admin' : $role;
    }
}

// Genera configurazione
$configContent = '<?php
/**
 * Configurazione principale LibreBot v2.0
 * Generata automaticamente il ' . date('Y-m-d H:i:s') . '
 */

// Configurazione del bot Telegram
$botToken = ' . var_export($botToken, true) . ';

// Configurazione di LibreNMS
$librenmsUrl = ' . var_export($librenmsUrl, true) . ';
$librenmsToken = ' . var_export($librenmsToken, true) . ';

// Chat ID e thread autorizzati
$allowedChatIds = ' . var_export($allowedChatIds, true) . ';
$allowedThreads = ' . var_export($allowedThreads, true) . ';

// Percorso dei file
$logFile = __DIR__ . \'/../logs/bot.log\';
$dbFile = __DIR__ . \'/../logs/bot.db\';

// Configurazioni sicurezza
$security = [
    \'rate_limiting\' => ' . var_export($enableRateLimit, true) . ',
    \'max_commands_per_minute\' => ' . $commandsPerMinute . ',
    \'max_commands_per_hour\' => ' . ($commandsPerMinute * 6) . ',
    \'allowed_shell_commands\' => ' . var_export($availableCommands, true) . ',
    \'ip_whitelist\' => ' . var_export($ipWhitelist, true) . ',
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

// Crea directory se necessarie
$directories = ['config', 'logs', 'lib', 'commands'];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "📁 Creata directory: $dir\n";
    }
}

// Scrivi file di configurazione
file_put_contents($configFile, $configContent);
echo "✅ Configurazione salvata in: $configFile\n";

// Test configurazione
echo "\n🧪 Test configurazione...\n";

// Test Telegram
echo "Testo connessione Telegram API...";
$telegramTest = @file_get_contents("https://api.telegram.org/bot$botToken/getMe");
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

// Test LibreNMS
echo "Test connessione LibreNMS API...";
$ch = curl_init("$librenmsUrl/api/v0/system");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Auth-Token: $librenmsToken"]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo " ✅ OK\n";
} else {
    echo " ❌ Errore (HTTP $httpCode)\n";
}

echo "\n🎉 Installazione completata!\n\n";

echo "📋 Prossimi passi:\n";
echo "1. Configura i permessi dei file se necessario\n";
echo "2. Avvia il bot con: php bot_v2.php\n";
echo "3. Testa con /help in Telegram\n";
echo "4. Controlla i log in logs/bot.log\n\n";

echo "🔗 Documentazione: README.md\n";
echo "🐛 Issues: https://github.com/paolo-trivi/telegram-librenms-bot/issues\n\n";

echo "✨ Buon monitoraggio con LibreBot v2.0!\n";