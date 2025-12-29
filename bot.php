<?php
/**
 * ===========================================================
 *  LibreBot v2.0
 * ===========================================================
 *  Author: Paolo Trivisonno
 *  Version 2.0 - Advanced Modular Bot for LibreNMS
 * 
 *  Advanced Security:
 *  - Configurable rate limiting
 *  - Role and permission system
 *  - Strict input validation
 *  - Structured logging
 *  - Auto-ban for failed attempts
 * 
 *  New Features:
 *  - Advanced alert management
 *  - Extended device management
 *  - Complete network troubleshooting
 *  - Dashboard and reporting
 *  - Health monitoring
 *  - SQLite database cache
 * 
 *  Modular Architecture:
 *  - SecurityManager for authentication
 *  - Structured logger
 *  - LibreNMS API wrapper
 *  - Separate command classes
 * ===========================================================
 */

// Load configuration and autoloader
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use LibreBot\Lib\Logger;
use LibreBot\Lib\SecurityManager;
use LibreBot\Lib\LibreNMSAPI;
use LibreBot\Lib\AlertTracker;
use LibreBot\Lib\TelegramBot;
use LibreBot\Lib\CommandDispatcher;
use LibreBot\Commands\AlertCommands;
use LibreBot\Commands\DeviceCommands;
use LibreBot\Commands\NetworkCommands;
use LibreBot\Commands\SystemCommands;
use LibreBot\Lib\Version;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\Translator;

// Initialize Translator
$translator = new Translator($language ?? 'en');
$translator->addLoader('php', new PhpFileLoader());

// Load languages
foreach (['en', 'it', 'fr', 'es', 'de', 'pt'] as $lang) {
    $resourceFile = __DIR__ . "/lang/$lang.php";
    if (file_exists($resourceFile)) {
        $translator->addResource('php', $resourceFile, $lang);
    }
}
$translator->setFallbackLocales(['en']);

// Initialization
$lastUpdateId = 0;

// Setup database
try {
    $db = new PDO("sqlite:$dbFile");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Initialize classes
$logger = new Logger($logFile, $debug['log_level'], $debug['verbose_logging']);
$security = new SecurityManager($config ?? [], $db, $logger);
$api = new LibreNMSAPI($librenmsUrl, $librenmsToken, $logger, $db, $config ?? []);
$alertTracker = new AlertTracker($db, $logger);
$telegram = new TelegramBot($botToken, $logger);

// Register bot start time
$alertTracker->setBotStat('start_time', (string)time());

// Initialize command handlers
$alertCommands = new AlertCommands($api, $logger, $security, $alertTracker, $translator);
$deviceCommands = new DeviceCommands($api, $logger, $security, $translator);
$networkCommands = new NetworkCommands($logger, $security, $config ?? [], $translator);
$systemCommands = new SystemCommands($api, $logger, $security, $config ?? [], $db, $alertTracker, $translator);

// Initialize Dispatcher
$dispatcher = new CommandDispatcher(
    $alertCommands,
    $deviceCommands,
    $networkCommands,
    $systemCommands,
    $translator
);

$logger->info("LibreBot v2.0 started", [
    'allowed_chats' => $allowedChatIds,
    'allowed_threads' => $allowedThreads,
    'security_enabled' => $config['security']['rate_limiting'] ?? false
]);

echo Version::getInfo() . " started!\n";
echo "Authorized chats: " . implode(', ', $allowedChatIds) . "\n";
echo "Security: " . ($config['security']['rate_limiting'] ? 'Active' : 'Inactive') . "\n";
echo "Language: " . strtoupper($language ?? 'en') . "\n";
echo "💾 Database: $dbFile\n";
echo "📝 Log: $logFile\n\n";
/**
 * Get user role from config
 */
function getUserRole(int $chatId, array $config): string
{
    $userPermissions = $config['userPermissions'] ?? [];
    return $userPermissions[$chatId] ?? 'viewer';
}

/**
 * Parse command and arguments
 */
function parseCommand($message)
{
    // Remove bot mentions
    $message = preg_replace('/@[\w_]+$/', '', $message);
    $message = trim($message);
    
    if (!str_starts_with($message, '/')) {
        return null;
    }
    
    $parts = explode(' ', $message);
    $command = substr($parts[0], 1); // Remove /
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

            // Parse command
            $parsedCommand = parseCommand($message);
            if (!$parsedCommand) {
                continue; // Not a command
            }

            $command = $parsedCommand['command'];
            $args = $parsedCommand['args'];
            $userRole = getUserRole($chatId, $config);

            $startTime = microtime(true);
            $success = false;
            $response = '';

            try {
                // Security checks
                if (!$security->isAuthorized($chatId, $threadId)) {
                    $response = "❌ Access denied.";
                    $telegram->sendMessage($chatId, $response, $threadId);
                    continue;
                }

                if ($security->isBanned($chatId)) {
                    $response = "🚫 User temporarily banned for too many failed attempts.";
                    $telegram->sendMessage($chatId, $response, $threadId);
                    continue;
                }

                if (!$security->checkRateLimit($chatId)) {
                    $response = "⏳ Rate limit exceeded. Try again later.";
                    $telegram->sendMessage($chatId, $response, $threadId);
                    continue;
                }

                // Check command permissions
                $commandPermission = $dispatcher->mapCommandToPermission($command);
                if (!$security->hasPermission($chatId, $commandPermission)) {
                    $response = "❌ Insufficient permissions for command /$command";
                    $telegram->sendMessage($chatId, $response, $threadId);
                    continue;
                }

                // Execute command
                $response = $dispatcher->dispatch($command, $args, $chatId, $threadId, $username, $userRole);
                $success = true;

            } catch (Exception $e) {
                $logger->error("Command execution failed", [
                    'command' => $command,
                    'error' => $e->getMessage(),
                    'user' => $username
                ]);
                $response = "❌ Internal error during command execution.";
            }

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            // Log command
            $security->logCommandExecution($chatId, $username, $command, $success, $executionTime);
            $logger->logTelegramCommand($chatId, $username, "/$command", $success, $executionTime, 
                $success ? null : $response);

            // Send response
            if (!empty($response)) {
                $telegram->sendMessage($chatId, $response, $threadId);
            }
        }

        // Periodic cleanup
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