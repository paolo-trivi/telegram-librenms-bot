<?php
/**
 * ===========================================================
 *  🤖 LibreBot v2.0
 * ===========================================================
 *  Autore: Paolo Trivisonno
 *  Versione 2.0 - Bot modulare avanzato per LibreNMS
 * 
 *  🔐 Sicurezza avanzata:
 *  - Rate limiting configurabile
 *  - Sistema di ruoli e permessi
 *  - Validazione input rigorosa
 *  - Logging strutturato
 *  - Ban automatico per tentativi falliti
 * 
 *  🆕 Nuove funzionalità:
 *  - Alert management avanzato
 *  - Device management esteso
 *  - Network troubleshooting completo
 *  - Dashboard e reportistica
 *  - Health monitoring
 *  - Cache con database SQLite
 * 
 *  📌 Architettura modulare con:
 *  - SecurityManager per autenticazione
 *  - Logger strutturato
 *  - LibreNMS API wrapper
 *  - Command classes separate
 * ===========================================================
 */

// Include configurazione e classi
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/lib/Logger.php';
require_once __DIR__ . '/lib/SecurityManager.php';
require_once __DIR__ . '/lib/LibreNMSAPI.php';
require_once __DIR__ . '/commands/AlertCommands.php';
require_once __DIR__ . '/commands/DeviceCommands.php';
require_once __DIR__ . '/commands/NetworkCommands.php';
require_once __DIR__ . '/commands/SystemCommands.php';

// Inizializzazione
$telegramApi = "https://api.telegram.org/bot$botToken";
$lastUpdateId = 0;

// Setup database
try {
    $db = new PDO("sqlite:$dbFile");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Inizializza classi
$logger = new Logger($logFile, $debug['log_level'], $debug['verbose_logging']);
$security = new SecurityManager($config ?? [], $db, $logger);
$api = new LibreNMSAPI($librenmsUrl, $librenmsToken, $logger, $db, $config ?? []);

// Inizializza command handlers
$alertCommands = new AlertCommands($api, $logger, $security);
$deviceCommands = new DeviceCommands($api, $logger, $security);
$networkCommands = new NetworkCommands($logger, $security, $config ?? []);
$systemCommands = new SystemCommands($api, $logger, $security, $config ?? [], $db);

$logger->info("LibreBot v2.0 started", [
    'allowed_chats' => $allowedChatIds,
    'allowed_threads' => $allowedThreads,
    'security_enabled' => $security['rate_limiting'] ?? false
]);

echo "🤖 LibreBot v2.0 avviato!\n";
echo "📋 Chat autorizzate: " . implode(', ', $allowedChatIds) . "\n";
echo "🔒 Sicurezza: " . ($security['rate_limiting'] ? 'Attiva' : 'Disattiva') . "\n";
echo "💾 Database: $dbFile\n";
echo "📝 Log: $logFile\n\n";

/**
 * Invia messaggio Telegram
 */
function sendTelegram($chatId, $text, $threadId = null)
{
    global $telegramApi, $logger, $security;
    
    $params = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'MarkdownV2'
    ];
    
    if ($threadId !== null) {
        $params['message_thread_id'] = $threadId;
    }
    
    // Escape special characters for MarkdownV2
    $text = escapeMarkdownV2($text);
    $params['text'] = $text;
    
    $url = $telegramApi . '/sendMessage?' . http_build_query($params);
    
    $logger->debug("Sending Telegram message", ['chat_id' => $chatId, 'length' => strlen($text)]);
    
    $response = @file_get_contents($url);
    if ($response === false) {
        $logger->error("Failed to send Telegram message", ['url' => $url]);
        return false;
    }
    
    return true;
}

/**
 * Escape testo per MarkdownV2
 */
function escapeMarkdownV2($text)
{
    // Per semplicità, rimuoviamo il markdown per ora
    return $text;
}

/**
 * Ottieni ruolo utente
 */
function getUserRole($chatId)
{
    global $userPermissions;
    return $userPermissions[$chatId] ?? 'viewer';
}

/**
 * Parse comando e argomenti
 */
function parseCommand($message)
{
    // Rimuovi menzioni bot
    $message = preg_replace('/@[\w_]+$/', '', $message);
    $message = trim($message);
    
    if (!str_starts_with($message, '/')) {
        return null;
    }
    
    $parts = explode(' ', $message);
    $command = substr($parts[0], 1); // Rimuovi /
    $args = array_slice($parts, 1);
    
    return [
        'command' => $command,
        'args' => $args,
        'full_text' => implode(' ', $args)
    ];
}

// ==================== MAIN LOOP ====================

while (true) {
    try {
        $url = "$telegramApi/getUpdates?timeout=10&offset=" . ($lastUpdateId + 1);
        $response = file_get_contents($url);
        $updates = json_decode($response, true);

        if (!$updates || !isset($updates['result'])) {
            sleep(2);
            continue;
        }

        foreach ($updates['result'] as $update) {
            $lastUpdateId = $update['update_id'];
            $message = $update['message']['text'] ?? '';
            $chatId = $update['message']['chat']['id'] ?? null;
            $threadId = $update['message']['message_thread_id'] ?? null;
            $username = $update['message']['from']['username'] ?? 'unknown';
            $userId = $update['message']['from']['id'] ?? 0;

            if (empty($message) || !$chatId) {
                continue;
            }

            $logger->debug("Received message", [
                'chat_id' => $chatId,
                'username' => $username,
                'message' => $message
            ]);

            // Parse comando
            $parsedCommand = parseCommand($message);
            if (!$parsedCommand) {
                continue; // Non è un comando
            }

            $command = $parsedCommand['command'];
            $args = $parsedCommand['args'];
            $userRole = getUserRole($chatId);

            $startTime = microtime(true);
            $success = false;
            $response = '';

            try {
                // Controlli di sicurezza
                if (!$security->isAuthorized($chatId, $threadId)) {
                    $response = "❌ Accesso negato.";
                    sendTelegram($chatId, $response, $threadId);
                    continue;
                }

                if ($security->isBanned($chatId)) {
                    $response = "🚫 Utente temporaneamente bannato per troppi tentativi falliti.";
                    sendTelegram($chatId, $response, $threadId);
                    continue;
                }

                if (!$security->checkRateLimit($chatId)) {
                    $response = "⏳ Rate limit superato. Riprova più tardi.";
                    sendTelegram($chatId, $response, $threadId);
                    continue;
                }

                // Verifica permessi comando
                $commandPermission = mapCommandToPermission($command);
                if (!$security->hasPermission($chatId, $commandPermission)) {
                    $response = "❌ Permessi insufficienti per il comando /$command";
                    sendTelegram($chatId, $response, $threadId);
                    continue;
                }

                // Esegui comando
                $response = executeCommand($command, $args, $chatId, $threadId, $username, $userRole);
                $success = true;

            } catch (Exception $e) {
                $logger->error("Command execution failed", [
                    'command' => $command,
                    'error' => $e->getMessage(),
                    'user' => $username
                ]);
                $response = "❌ Errore interno durante l'esecuzione del comando.";
            }

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            // Log comando
            $security->logCommandExecution($chatId, $username, $command, $success, $executionTime);
            $logger->logTelegramCommand($chatId, $username, "/$command", $success, $executionTime, 
                $success ? null : $response);

            // Invia risposta
            if (!empty($response)) {
                sendTelegram($chatId, $response, $threadId);
            }
        }

        // Cleanup periodico
        if (rand(1, 100) === 1) {
            $api->cleanExpiredCache();
            $logger->debug("Performed periodic cleanup");
        }

    } catch (Exception $e) {
        $logger->error("Main loop error: " . $e->getMessage());
        sleep(5);
    }

    sleep(1);
}

// ==================== COMMAND EXECUTION ====================

function executeCommand($command, $args, $chatId, $threadId, $username, $userRole)
{
    global $alertCommands, $deviceCommands, $networkCommands, $systemCommands;

    switch ($command) {
        // ====== ALERT COMMANDS ======
        case 'list':
            return $alertCommands->listAlerts($chatId, $threadId);

        case 'ack':
            if (empty($args[0])) return "❌ Uso: /ack <alert_id> [nota]";
            $alertId = $args[0];
            $note = implode(' ', array_slice($args, 1)) ?: 'Acknowledged via Telegram';
            return $alertCommands->acknowledgeAlert($alertId, $note, $chatId, $username);

        case 'alert_stats':
            $period = $args[0] ?? 'today';
            return $alertCommands->getAlertStats($period);

        case 'alert_history':
            if (empty($args[0])) return "❌ Uso: /alert_history <device_id>";
            return $alertCommands->getAlertHistory($args[0], $args[1] ?? 10);

        case 'bulk_ack':
            if (empty($args[0])) return "❌ Uso: /bulk_ack <pattern> [nota]";
            $pattern = $args[0];
            $note = implode(' ', array_slice($args, 1)) ?: 'Bulk acknowledged';
            return $alertCommands->bulkAcknowledge($pattern, $note, $username);

        case 'top_alerts':
            return $alertCommands->getTopAlerts($args[0] ?? 10);

        case 'escalate':
            if (count($args) < 2) return "❌ Uso: /escalate <alert_id> <motivo>";
            return $alertCommands->escalateAlert($args[0], implode(' ', array_slice($args, 1)), $username);

        // ====== DEVICE COMMANDS ======
        case 'list_device':
            return $deviceCommands->listDevices($args[0] ?? '');

        case 'device_status':
            if (empty($args[0])) return "❌ Uso: /device_status <device_id>";
            return $deviceCommands->getDeviceStatus($args[0]);

        case 'port_status':
            if (count($args) < 2) return "❌ Uso: /port_status <device_id> <port_name>";
            return $deviceCommands->getPortStatus($args[0], $args[1]);

        case 'bandwidth_top':
            return $deviceCommands->getTopBandwidth($args[0] ?? 10);

        case 'device_add':
            if (empty($args[0])) return "❌ Uso: /device_add <hostname> [community]";
            return $deviceCommands->addDevice($args[0], $args[1] ?? 'public', $username);

        case 'device_remove':
            if (empty($args[0])) return "❌ Uso: /device_remove <device_id>";
            return $deviceCommands->removeDevice($args[0], $username);

        case 'device_redetect':
            if (empty($args[0])) return "❌ Uso: /device_redetect <device_id>";
            return $deviceCommands->rediscoverDevice($args[0], $username);

        case 'maintenance':
            if (count($args) < 2) return "❌ Uso: /maintenance <device_id> <on/off> [durata_ore]";
            $duration = isset($args[2]) ? intval($args[2]) * 3600 : 3600;
            return $deviceCommands->setMaintenanceMode($args[0], $args[1], $duration, 'Maintenance via Telegram', $username);

        case 'performance_report':
            if (empty($args[0])) return "❌ Uso: /performance_report <device_id> [periodo]";
            return $deviceCommands->getPerformanceReport($args[0], $args[1] ?? '24h');

        case 'dashboard':
            return $deviceCommands->getDeviceDashboard();

        // ====== NETWORK COMMANDS ======
        case 'ping':
            if (empty($args[0])) return "❌ Uso: /ping <host>";
            return $networkCommands->ping($args[0], $username);

        case 'trace':
            if (empty($args[0])) return "❌ Uso: /trace <host>";
            return $networkCommands->traceroute($args[0], $username);

        case 'mtr':
            if (empty($args[0])) return "❌ Uso: /mtr <host> [count]";
            return $networkCommands->mtr($args[0], $args[1] ?? 10, $username);

        case 'ns':
            if (empty($args[0])) return "❌ Uso: /ns <host>";
            return $networkCommands->nslookup($args[0], $username);

        case 'dig':
            if (empty($args[0])) return "❌ Uso: /dig <domain> [record_type]";
            return $networkCommands->dig($args[0], $args[1] ?? 'A', $username);

        case 'whois':
            if (empty($args[0])) return "❌ Uso: /whois <domain|ip>";
            return $networkCommands->whois($args[0], $username);

        case 'port_scan':
            if (empty($args[0])) return "❌ Uso: /port_scan <host> [port_range]";
            return $networkCommands->portScan($args[0], $args[1] ?? '1-1000', $username);

        case 'ssl_check':
            if (empty($args[0])) return "❌ Uso: /ssl_check <host> [port]";
            return $networkCommands->sslCheck($args[0], $args[1] ?? 443, $username);

        case 'http_check':
            if (empty($args[0])) return "❌ Uso: /http_check <url>";
            return $networkCommands->httpCheck($args[0], $username);

        case 'network_summary':
            if (empty($args[0])) return "❌ Uso: /network_summary <host>";
            return $networkCommands->networkSummary($args[0], $username);

        // ====== SYSTEM COMMANDS ======
        case 'help':
            return $systemCommands->getHelp($userRole);

        case 'bot_status':
            return $systemCommands->getBotStatus();

        case 'bot_stats':
            return $systemCommands->getBotStats($args[0] ?? '24h');

        case 'health':
            return $systemCommands->getHealthCheck();

        case 'log':
            return $systemCommands->getLog($args[0] ?? 50);

        case 'system_dashboard':
            return $systemCommands->getSystemDashboard();

        // ====== UTILITY COMMANDS ======
        case 'calc':
            if (empty($args[0])) return "❌ Uso: /calc <cidr> (es. 192.168.1.0/24)";
            return $systemCommands->calculateSubnet($args[0]);

        case 'convert':
            if (count($args) < 3) return "❌ Uso: /convert <value> <from> <to>";
            return $systemCommands->convertUnits($args[0], $args[1], $args[2]);

        case 'time':
            return $systemCommands->getTimeInTimezone($args[0] ?? 'Europe/Rome');

        // ====== LEGACY SUPPORT ======
        case 'nmap':
            if (empty($args[0])) return "❌ Uso: /nmap <host>";
            return $networkCommands->portScan($args[0], '1-1000', $username);

        default:
            return "❌ Comando sconosciuto: /$command\nUsa /help per vedere i comandi disponibili.";
    }
}

/**
 * Mappa comando a permesso
 */
function mapCommandToPermission($command)
{
    $mapping = [
        'list' => 'alert_list',
        'ack' => 'alert_ack',
        'alert_stats' => 'alert_stats',
        'alert_history' => 'alert_history',
        'bulk_ack' => 'alert_bulk_ack',
        'escalate' => 'alert_escalate',
        'top_alerts' => 'alert_list',
        
        'list_device' => 'device_list',
        'device_status' => 'device_status',
        'port_status' => 'device_status',
        'bandwidth_top' => 'device_list',
        'device_add' => 'device_add',
        'device_remove' => 'device_remove',
        'device_redetect' => 'device_redetect',
        'maintenance' => 'device_maintenance',
        'performance_report' => 'device_status',
        'dashboard' => 'device_dashboard',
        
        'ping' => 'network_ping',
        'trace' => 'network_trace',
        'mtr' => 'network_mtr',
        'ns' => 'network_ns',
        'dig' => 'network_dig',
        'whois' => 'network_whois',
        'port_scan' => 'network_port_scan',
        'ssl_check' => 'network_ssl',
        'http_check' => 'network_http',
        'network_summary' => 'network_ping',
        'nmap' => 'network_port_scan',
        
        'help' => 'system_help',
        'bot_status' => 'bot_status',
        'bot_stats' => 'bot_stats',
        'health' => 'bot_health',
        'log' => 'system_log',
        'system_dashboard' => 'system_dashboard',
        
        'calc' => 'system_calc',
        'convert' => 'system_convert',
        'time' => 'system_time'
    ];
    
    return $mapping[$command] ?? $command;
}