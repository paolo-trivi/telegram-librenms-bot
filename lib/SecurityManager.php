<?php

/**
 * SecurityManager - Gestione sicurezza e autenticazione
 * 
 * @author Paolo Trivisonno
 * @version 2.0
 */
class SecurityManager
{
    private $config;
    private $db;
    private $logger;

    public function __construct($config, $db, $logger)
    {
        $this->config = $config;
        $this->db = $db;
        $this->logger = $logger;
        $this->initializeTables();
    }

    /**
     * Inizializza le tabelle del database per il rate limiting
     */
    private function initializeTables()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS rate_limit (
                chat_id INTEGER,
                command_time INTEGER,
                PRIMARY KEY (chat_id, command_time)
            )
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS failed_attempts (
                chat_id INTEGER PRIMARY KEY,
                attempts INTEGER DEFAULT 0,
                last_attempt INTEGER,
                banned_until INTEGER DEFAULT 0
            )
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS command_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                chat_id INTEGER,
                username TEXT,
                command TEXT,
                timestamp INTEGER,
                success INTEGER,
                execution_time REAL
            )
        ");
    }

    /**
     * Verifica se un utente è autorizzato
     */
    public function isAuthorized($chatId, $threadId = null)
    {
        global $allowedChatIds, $allowedThreads;
        
        if (!in_array($chatId, $allowedChatIds)) {
            $this->logFailedAttempt($chatId, 'unauthorized_chat');
            return false;
        }

        if ($chatId < 0 && $threadId !== null && !in_array($threadId, $allowedThreads)) {
            $this->logFailedAttempt($chatId, 'unauthorized_thread');
            return false;
        }

        return true;
    }

    /**
     * Verifica se un utente è bannato
     */
    public function isBanned($chatId)
    {
        $stmt = $this->db->prepare("SELECT banned_until FROM failed_attempts WHERE chat_id = ?");
        $stmt->execute([$chatId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && $result['banned_until'] > time()) {
            return true;
        }

        return false;
    }

    /**
     * Verifica il rate limiting
     */
    public function checkRateLimit($chatId)
    {
        if (!$this->config['security']['rate_limiting']) {
            return true;
        }

        $now = time();
        $minuteAgo = $now - 60;
        $hourAgo = $now - 3600;

        // Pulisci vecchi record
        $this->db->exec("DELETE FROM rate_limit WHERE command_time < $hourAgo");

        // Conta comandi nell'ultimo minuto
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM rate_limit WHERE chat_id = ? AND command_time > ?");
        $stmt->execute([$chatId, $minuteAgo]);
        $commandsLastMinute = $stmt->fetchColumn();

        if ($commandsLastMinute >= $this->config['security']['max_commands_per_minute']) {
            $this->logger->warning("Rate limit exceeded for chat_id $chatId: $commandsLastMinute commands in last minute");
            return false;
        }

        // Conta comandi nell'ultima ora
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM rate_limit WHERE chat_id = ? AND command_time > ?");
        $stmt->execute([$chatId, $hourAgo]);
        $commandsLastHour = $stmt->fetchColumn();

        if ($commandsLastHour >= $this->config['security']['max_commands_per_hour']) {
            $this->logger->warning("Rate limit exceeded for chat_id $chatId: $commandsLastHour commands in last hour");
            return false;
        }

        // Registra il comando
        $stmt = $this->db->prepare("INSERT INTO rate_limit (chat_id, command_time) VALUES (?, ?)");
        $stmt->execute([$chatId, $now]);

        return true;
    }

    /**
     * Verifica i permessi per un comando
     */
    public function hasPermission($chatId, $command)
    {
        global $userPermissions, $userRoles;

        // Se non ci sono permessi configurati, usa il comportamento legacy
        if (empty($userPermissions)) {
            return true;
        }

        $userRole = $userPermissions[$chatId] ?? 'viewer';
        $allowedCommands = $userRoles[$userRole] ?? [];

        // Verifica permessi wildcard (es. alert_*)
        foreach ($allowedCommands as $allowedCommand) {
            if (str_ends_with($allowedCommand, '*')) {
                $prefix = rtrim($allowedCommand, '*');
                if (str_starts_with($command, $prefix)) {
                    return true;
                }
            } elseif ($allowedCommand === $command) {
                return true;
            }
        }

        $this->logFailedAttempt($chatId, "permission_denied_$command");
        return false;
    }

    /**
     * Valida e sanitizza input per comandi shell
     */
    public function validateShellInput($input, $type = 'host')
    {
        switch ($type) {
            case 'host':
                // Valida hostname o IP
                if (filter_var($input, FILTER_VALIDATE_IP)) {
                    return $this->isIpWhitelisted($input) ? $input : false;
                }
                // Valida hostname
                if (preg_match('/^[a-zA-Z0-9\.\-]+$/', $input) && strlen($input) <= 255) {
                    return $input;
                }
                break;
                
            case 'domain':
                // Valida domain name
                if (preg_match('/^[a-zA-Z0-9\.\-]+$/', $input) && strlen($input) <= 255) {
                    return $input;
                }
                break;
                
            case 'port':
                // Valida porta
                $port = intval($input);
                if ($port >= 1 && $port <= 65535) {
                    return $port;
                }
                break;
                
            case 'port_range':
                // Valida range porte (es. 80-443)
                if (preg_match('/^(\d+)-(\d+)$/', $input, $matches)) {
                    $start = intval($matches[1]);
                    $end = intval($matches[2]);
                    if ($start >= 1 && $end <= 65535 && $start <= $end && ($end - $start) <= 1000) {
                        return "$start-$end";
                    }
                }
                break;
        }
        
        return false;
    }

    /**
     * Verifica se un IP è nella whitelist
     */
    private function isIpWhitelisted($ip)
    {
        $whitelist = $this->config['security']['ip_whitelist'];
        
        foreach ($whitelist as $cidr) {
            if ($this->ipInRange($ip, $cidr)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Verifica se un IP è in un range CIDR
     */
    private function ipInRange($ip, $cidr)
    {
        list($range, $netmask) = explode('/', $cidr, 2);
        $range_decimal = ip2long($range);
        $ip_decimal = ip2long($ip);
        $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
        $netmask_decimal = ~ $wildcard_decimal;
        
        return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
    }

    /**
     * Registra un tentativo fallito
     */
    private function logFailedAttempt($chatId, $reason)
    {
        if (!$this->config['security']['log_failed_attempts']) {
            return;
        }

        $now = time();
        
        // Incrementa counter tentativi falliti
        $stmt = $this->db->prepare("
            INSERT OR REPLACE INTO failed_attempts (chat_id, attempts, last_attempt, banned_until) 
            VALUES (?, COALESCE((SELECT attempts FROM failed_attempts WHERE chat_id = ?), 0) + 1, ?, 0)
        ");
        $stmt->execute([$chatId, $chatId, $now]);

        // Verifica se superato il limite
        $stmt = $this->db->prepare("SELECT attempts FROM failed_attempts WHERE chat_id = ?");
        $stmt->execute([$chatId]);
        $attempts = $stmt->fetchColumn();

        if ($attempts >= $this->config['security']['max_failed_attempts']) {
            // Banna l'utente
            $banUntil = $now + $this->config['security']['ban_duration'];
            $stmt = $this->db->prepare("UPDATE failed_attempts SET banned_until = ? WHERE chat_id = ?");
            $stmt->execute([$banUntil, $chatId]);
            
            $this->logger->warning("User $chatId banned until " . date('Y-m-d H:i:s', $banUntil) . " after $attempts failed attempts");
        }

        $this->logger->warning("Failed attempt from chat_id $chatId: $reason (attempt $attempts)");
    }

    /**
     * Registra l'esecuzione di un comando
     */
    public function logCommandExecution($chatId, $username, $command, $success, $executionTime)
    {
        $stmt = $this->db->prepare("
            INSERT INTO command_history (chat_id, username, command, timestamp, success, execution_time)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$chatId, $username, $command, time(), $success ? 1 : 0, $executionTime]);
    }

    /**
     * Ottieni statistiche di sicurezza
     */
    public function getSecurityStats()
    {
        $stats = [];
        
        // Comandi nelle ultime 24 ore
        $yesterday = time() - 86400;
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM command_history WHERE timestamp > ?");
        $stmt->execute([$yesterday]);
        $stats['commands_24h'] = $stmt->fetchColumn();

        // Tentativi falliti nelle ultime 24 ore
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM failed_attempts WHERE last_attempt > ?");
        $stmt->execute([$yesterday]);
        $stats['failed_attempts_24h'] = $stmt->fetchColumn();

        // Utenti attualmente bannati
        $now = time();
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM failed_attempts WHERE banned_until > ?");
        $stmt->execute([$now]);
        $stats['banned_users'] = $stmt->fetchColumn();

        return $stats;
    }

    /**
     * Reset failed attempts per un utente
     */
    public function resetFailedAttempts($chatId)
    {
        $stmt = $this->db->prepare("DELETE FROM failed_attempts WHERE chat_id = ?");
        $stmt->execute([$chatId]);
        $this->logger->info("Reset failed attempts for chat_id $chatId");
    }
}