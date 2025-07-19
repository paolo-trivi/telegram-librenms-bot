<?php

/**
 * SystemCommands - Comandi sistema e bot management
 * 
 * @author Paolo Trivisonno
 * @version 2.0
 */
class SystemCommands
{
    private $api;
    private $logger;
    private $security;
    private $config;
    private $db;

    public function __construct($api, $logger, $security, $config, $db)
    {
        $this->api = $api;
        $this->logger = $logger;
        $this->security = $security;
        $this->config = $config;
        $this->db = $db;
    }

    /**
     * Help menu completo
     */
    public function getHelp($userRole = 'admin')
    {
        global $userRoles;
        
        $allowedCommands = $userRoles[$userRole] ?? $userRoles['viewer'];
        
        $commands = [
            'alert' => [
                'alert_list' => '/list → Elenca alert attivi',
                'alert_ack' => '/ack <id> [nota] → Acknowledge alert',
                'alert_stats' => '/alert_stats → Statistiche alert',
                'alert_history' => '/alert_history <device_id> → Storico alert dispositivo',
                'alert_bulk_ack' => '/bulk_ack <pattern> [nota] → ACK multiplo con pattern',
                'alert_escalate' => '/escalate <alert_id> <motivo> → Escalation alert'
            ],
            'device' => [
                'device_list' => '/list_device [filtro] → Lista dispositivi',
                'device_status' => '/device_status <device_id> → Status dettagliato',
                'device_add' => '/device_add <hostname> [community] → Aggiungi dispositivo',
                'device_remove' => '/device_remove <device_id> → Rimuovi dispositivo',
                'device_maintenance' => '/maintenance <device_id> <on/off> [durata] → Modalità manutenzione',
                'device_dashboard' => '/dashboard → Dashboard dispositivi'
            ],
            'network' => [
                'network_ping' => '/ping <host> → Ping (5 pacchetti)',
                'network_trace' => '/trace <host> → Traceroute',
                'network_mtr' => '/mtr <host> [count] → My Traceroute',
                'network_ns' => '/ns <host> → NSLookup',
                'network_dig' => '/dig <domain> [type] → DNS lookup avanzato',
                'network_whois' => '/whois <domain/ip> → WHOIS lookup',
                'network_ssl' => '/ssl_check <host> [port] → Verifica SSL',
                'network_http' => '/http_check <url> → Test HTTP/HTTPS'
            ],
            'system' => [
                'bot_status' => '/bot_status → Status del bot',
                'bot_stats' => '/bot_stats → Statistiche utilizzo',
                'bot_health' => '/health → Health check sistema',
                'system_log' => '/log [lines] → Mostra log',
                'system_help' => '/help → Questo menu'
            ]
        ];

        $text = "📖 LibreBot v2.0 - Comandi disponibili:\n\n";

        foreach ($commands as $category => $categoryCommands) {
            $availableCommands = [];
            foreach ($categoryCommands as $permission => $description) {
                if ($this->hasPermission($allowedCommands, $permission)) {
                    $availableCommands[] = $description;
                }
            }
            
            if (!empty($availableCommands)) {
                $categoryName = ucfirst($category);
                $text .= "🔹 **$categoryName**\n";
                foreach ($availableCommands as $cmd) {
                    $text .= "  $cmd\n";
                }
                $text .= "\n";
            }
        }

        $text .= "👤 Ruolo: $userRole\n";
        $text .= "🔒 Rate limit: " . ($this->config['security']['rate_limiting'] ? 'Attivo' : 'Disattivo');

        return $text;
    }

    /**
     * Status del bot
     */
    public function getBotStatus()
    {
        try {
            $startTime = $this->getBotStartTime();
            $uptime = $startTime ? time() - $startTime : 0;
            $uptimeFormatted = $this->formatDuration($uptime);

            $text = "🤖 LibreBot Status\n\n";
            $text .= "✅ Status: Online\n";
            $text .= "⏰ Uptime: $uptimeFormatted\n";
            $text .= "💾 Memory: " . round(memory_get_usage(true) / 1024 / 1024, 2) . " MB\n";
            $text .= "📊 Peak Memory: " . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB\n";
            $text .= "🔄 PID: " . getmypid() . "\n";

            // Test connessione LibreNMS
            $apiTest = $this->api->testConnection();
            if ($apiTest['success']) {
                $text .= "🟢 LibreNMS API: Online (v" . ($apiTest['version'] ?? 'unknown') . ")\n";
            } else {
                $text .= "🔴 LibreNMS API: Offline\n";
            }

            // Statistiche sicurezza
            $secStats = $this->security->getSecurityStats();
            $text .= "🛡️ Comandi 24h: " . $secStats['commands_24h'] . "\n";
            $text .= "⚠️ Failed attempts 24h: " . $secStats['failed_attempts_24h'] . "\n";
            $text .= "🚫 Banned users: " . $secStats['banned_users'] . "\n";

            return $text;

        } catch (Exception $e) {
            $this->logger->error("Error getting bot status: " . $e->getMessage());
            return "❌ Errore nel recupero dello status: " . $e->getMessage();
        }
    }

    /**
     * Statistiche utilizzo bot
     */
    public function getBotStats($period = '24h')
    {
        try {
            $hours = $this->parsePeriod($period);
            $since = time() - ($hours * 3600);

            // Statistiche da database
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_commands,
                    COUNT(DISTINCT chat_id) as unique_users,
                    AVG(execution_time) as avg_execution_time,
                    command,
                    COUNT(*) as command_count
                FROM command_history 
                WHERE timestamp > ? 
                GROUP BY command 
                ORDER BY command_count DESC 
                LIMIT 10
            ");
            $stmt->execute([$since]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $totalCommands = 0;
            $uniqueUsers = 0;
            $avgTime = 0;
            $topCommands = [];

            if (!empty($results)) {
                $totalCommands = $results[0]['total_commands'] ?? 0;
                $uniqueUsers = $results[0]['unique_users'] ?? 0;
                $avgTime = round($results[0]['avg_execution_time'] ?? 0, 2);
                
                foreach ($results as $row) {
                    $topCommands[] = $row['command'] . ' (' . $row['command_count'] . 'x)';
                }
            }

            $text = "📊 Bot Statistics ($period)\n\n";
            $text .= "🔢 Comandi totali: $totalCommands\n";
            $text .= "👥 Utenti unici: $uniqueUsers\n";
            $text .= "⚡ Tempo medio esecuzione: {$avgTime}ms\n";

            if (!empty($topCommands)) {
                $text .= "\n🏆 Top comandi:\n";
                foreach (array_slice($topCommands, 0, 5) as $cmd) {
                    $text .= "• $cmd\n";
                }
            }

            // Log statistics
            $logStats = $this->logger->getLogStats($hours);
            $text .= "\n📝 Log entries: " . $logStats['total'] . "\n";
            if (!empty($logStats['by_level'])) {
                foreach ($logStats['by_level'] as $level => $count) {
                    if ($count > 0) {
                        $emoji = $this->getLevelEmoji($level);
                        $text .= "$emoji $level: $count\n";
                    }
                }
            }

            return $text;

        } catch (Exception $e) {
            $this->logger->error("Error getting bot stats: " . $e->getMessage());
            return "❌ Errore nel recupero delle statistiche: " . $e->getMessage();
        }
    }

    /**
     * Health check completo
     */
    public function getHealthCheck()
    {
        try {
            $health = [
                'overall' => '✅',
                'issues' => []
            ];

            $text = "🏥 Health Check\n\n";

            // Test LibreNMS API
            $apiTest = $this->api->testConnection();
            if ($apiTest['success']) {
                $text .= "✅ LibreNMS API: OK\n";
            } else {
                $text .= "❌ LibreNMS API: FAILED\n";
                $health['overall'] = '❌';
                $health['issues'][] = 'LibreNMS API unreachable';
            }

            // Test database
            try {
                $this->db->query("SELECT 1");
                $text .= "✅ Database: OK\n";
            } catch (Exception $e) {
                $text .= "❌ Database: FAILED\n";
                $health['overall'] = '❌';
                $health['issues'][] = 'Database connection failed';
            }

            // Test file permissions
            $writableFiles = [
                $this->config['logFile'] ?? '/tmp/test.log',
                $this->config['dbFile'] ?? '/tmp/test.db'
            ];

            $permissionIssues = 0;
            foreach ($writableFiles as $file) {
                $dir = dirname($file);
                if (!is_writable($dir)) {
                    $permissionIssues++;
                }
            }

            if ($permissionIssues === 0) {
                $text .= "✅ File Permissions: OK\n";
            } else {
                $text .= "⚠️ File Permissions: $permissionIssues issues\n";
                $health['issues'][] = 'File permission issues detected';
            }

            // Test memory usage
            $memoryUsage = memory_get_usage(true) / 1024 / 1024; // MB
            if ($memoryUsage < 100) {
                $text .= "✅ Memory Usage: " . round($memoryUsage, 1) . "MB\n";
            } elseif ($memoryUsage < 200) {
                $text .= "⚠️ Memory Usage: " . round($memoryUsage, 1) . "MB\n";
            } else {
                $text .= "❌ Memory Usage: " . round($memoryUsage, 1) . "MB (HIGH)\n";
                $health['overall'] = $health['overall'] === '✅' ? '⚠️' : '❌';
                $health['issues'][] = 'High memory usage';
            }

            // Test external commands
            $commands = ['ping', 'traceroute', 'nslookup'];
            $missingCommands = [];
            foreach ($commands as $cmd) {
                if (!shell_exec("which $cmd")) {
                    $missingCommands[] = $cmd;
                }
            }

            if (empty($missingCommands)) {
                $text .= "✅ External Commands: OK\n";
            } else {
                $text .= "⚠️ External Commands: Missing " . implode(', ', $missingCommands) . "\n";
            }

            $text .= "\n" . $health['overall'] . " Overall Status: " . 
                     ($health['overall'] === '✅' ? 'HEALTHY' : 
                     ($health['overall'] === '⚠️' ? 'WARNING' : 'CRITICAL')) . "\n";

            if (!empty($health['issues'])) {
                $text .= "\n🔧 Issues found:\n";
                foreach ($health['issues'] as $issue) {
                    $text .= "• $issue\n";
                }
            }

            return $text;

        } catch (Exception $e) {
            $this->logger->error("Error during health check: " . $e->getMessage());
            return "❌ Errore durante health check: " . $e->getMessage();
        }
    }

    /**
     * Log del bot
     */
    public function getLog($lines = 50)
    {
        try {
            $logContent = $this->logger->getTailLines($lines);
            return "📜 Ultimi $lines log entries:\n\n```\n$logContent\n```";
        } catch (Exception $e) {
            return "❌ Errore nel recupero del log: " . $e->getMessage();
        }
    }

    /**
     * Dashboard generale sistema
     */
    public function getSystemDashboard()
    {
        try {
            $text = "🎛️ System Dashboard\n\n";

            // Alert summary
            $alerts = $this->api->getActiveAlerts();
            $alertCount = count($alerts['alerts'] ?? []);
            $alertEmoji = $alertCount > 0 ? '🔴' : '✅';
            $text .= "$alertEmoji Alert attivi: $alertCount\n";

            // Device summary
            $devices = $this->api->getDevices('active');
            $deviceList = $devices['devices'] ?? [];
            $totalDevices = count($deviceList);
            $onlineDevices = count(array_filter($deviceList, function($d) { 
                return $d['status'] ?? false; 
            }));
            $text .= "📟 Dispositivi: $onlineDevices/$totalDevices online\n";

            // System health
            $librenmsTest = $this->api->testConnection();
            $librenmsStatus = $librenmsTest['success'] ? '✅' : '❌';
            $text .= "$librenmsStatus LibreNMS API\n";

            // Bot stats
            $secStats = $this->security->getSecurityStats();
            $text .= "🤖 Comandi 24h: " . $secStats['commands_24h'] . "\n";

            if ($totalDevices > 0) {
                $availability = round(($onlineDevices / $totalDevices) * 100, 1);
                $text .= "📊 Disponibilità: {$availability}%\n";
            }

            return $text;

        } catch (Exception $e) {
            $this->logger->error("Error getting system dashboard: " . $e->getMessage());
            return "❌ Errore nel recupero del dashboard: " . $e->getMessage();
        }
    }

    /**
     * Utility commands
     */
    public function calculateSubnet($cidr)
    {
        if (!preg_match('/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\/(\d{1,2})$/', $cidr, $matches)) {
            return "❌ Formato CIDR non valido. Usa: 192.168.1.0/24";
        }

        $ip = $matches[1];
        $prefix = intval($matches[2]);

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || $prefix < 0 || $prefix > 32) {
            return "❌ IP o prefisso non valido";
        }

        $ipLong = ip2long($ip);
        $netmask = (-1 << (32 - $prefix)) & 0xFFFFFFFF;
        $network = $ipLong & $netmask;
        $broadcast = $network | (~$netmask & 0xFFFFFFFF);

        $networkIp = long2ip($network);
        $broadcastIp = long2ip($broadcast);
        $firstHost = long2ip($network + 1);
        $lastHost = long2ip($broadcast - 1);
        $totalHosts = pow(2, 32 - $prefix) - 2;
        $netmaskIp = long2ip($netmask);

        $text = "🧮 Calcolo Subnet $cidr:\n\n";
        $text .= "🌐 Network: $networkIp\n";
        $text .= "📡 Broadcast: $broadcastIp\n";
        $text .= "🖥️ Primo host: $firstHost\n";
        $text .= "🖥️ Ultimo host: $lastHost\n";
        $text .= "🔢 Host totali: $totalHosts\n";
        $text .= "🎭 Netmask: $netmaskIp\n";

        return $text;
    }

    /**
     * Converti unità
     */
    public function convertUnits($value, $from, $to)
    {
        $conversions = [
            'bytes' => [
                'kb' => 1024,
                'mb' => 1024 * 1024,
                'gb' => 1024 * 1024 * 1024,
                'tb' => 1024 * 1024 * 1024 * 1024
            ],
            'bps' => [
                'kbps' => 1000,
                'mbps' => 1000 * 1000,
                'gbps' => 1000 * 1000 * 1000
            ]
        ];

        $from = strtolower($from);
        $to = strtolower($to);
        $numValue = floatval($value);

        foreach ($conversions as $type => $typeConversions) {
            if (isset($typeConversions[$from]) && isset($typeConversions[$to])) {
                $fromFactor = $typeConversions[$from];
                $toFactor = $typeConversions[$to];
                $result = ($numValue * $fromFactor) / $toFactor;
                
                return "🔄 Conversione:\n$value $from = " . number_format($result, 4) . " $to";
            }
        }

        return "❌ Conversione non supportata da $from a $to\nTipi supportati: bytes (kb,mb,gb,tb), bps (kbps,mbps,gbps)";
    }

    /**
     * Tempo in timezone
     */
    public function getTimeInTimezone($timezone = 'Europe/Rome')
    {
        try {
            $tz = new DateTimeZone($timezone);
            $datetime = new DateTime('now', $tz);
            $timeString = $datetime->format('Y-m-d H:i:s T');
            
            return "🕒 Orario in $timezone:\n$timeString";
        } catch (Exception $e) {
            return "❌ Timezone non valida: $timezone";
        }
    }

    // ==================== UTILITY METHODS ====================

    private function hasPermission($allowedCommands, $permission)
    {
        foreach ($allowedCommands as $allowed) {
            if (str_ends_with($allowed, '*')) {
                $prefix = rtrim($allowed, '*');
                if (str_starts_with($permission, $prefix)) {
                    return true;
                }
            } elseif ($allowed === $permission) {
                return true;
            }
        }
        return false;
    }

    private function getBotStartTime()
    {
        // Implementare tracking start time
        return time() - 3600; // Mock: 1 ora fa
    }

    private function formatDuration($seconds)
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        if ($days > 0) {
            return "{$days}d {$hours}h {$minutes}m";
        } elseif ($hours > 0) {
            return "{$hours}h {$minutes}m";
        } else {
            return "{$minutes}m";
        }
    }

    private function parsePeriod($period)
    {
        switch (strtolower($period)) {
            case '1h': return 1;
            case '6h': return 6;
            case '12h': return 12;
            case '24h': 
            case '1d': return 24;
            case '7d':
            case '1w': return 168;
            default: return 24;
        }
    }

    private function getLevelEmoji($level)
    {
        $emojis = [
            'DEBUG' => '🔍',
            'INFO' => 'ℹ️',
            'WARNING' => '⚠️',
            'ERROR' => '❌'
        ];
        return $emojis[$level] ?? 'ℹ️';
    }
}