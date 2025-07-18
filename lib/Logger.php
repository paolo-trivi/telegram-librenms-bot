<?php

/**
 * Logger - Sistema di logging strutturato
 * 
 * @author Paolo Trivisonno
 * @version 2.0
 */
class Logger
{
    private $logFile;
    private $logLevel;
    private $verboseLogging;
    private $maxLogSize = 10485760; // 10MB
    private $maxLogFiles = 5;

    const LEVELS = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3
    ];

    public function __construct($logFile, $logLevel = 'INFO', $verboseLogging = false)
    {
        $this->logFile = $logFile;
        $this->logLevel = $logLevel;
        $this->verboseLogging = $verboseLogging;
        
        // Crea directory se non esiste
        $dir = dirname($logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * Log debug
     */
    public function debug($message, $context = [])
    {
        $this->log('DEBUG', $message, $context);
    }

    /**
     * Log info
     */
    public function info($message, $context = [])
    {
        $this->log('INFO', $message, $context);
    }

    /**
     * Log warning
     */
    public function warning($message, $context = [])
    {
        $this->log('WARNING', $message, $context);
    }

    /**
     * Log error
     */
    public function error($message, $context = [])
    {
        $this->log('ERROR', $message, $context);
    }

    /**
     * Log principale
     */
    private function log($level, $message, $context = [])
    {
        if (self::LEVELS[$level] < self::LEVELS[$this->logLevel]) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $pid = getmypid();
        
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => $level,
            'pid' => $pid,
            'message' => $message
        ];

        if (!empty($context)) {
            $logEntry['context'] = $context;
        }

        if ($this->verboseLogging) {
            $logEntry['memory_usage'] = memory_get_usage(true);
            $logEntry['memory_peak'] = memory_get_peak_usage(true);
        }

        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n";
        
        // Rotazione log se necessario
        $this->rotateLogIfNeeded();
        
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
        
        // Output su console se debug abilitato
        global $debug;
        if (isset($debug['enabled']) && $debug['enabled']) {
            echo "[$timestamp] [$level] $message\n";
        }
    }

    /**
     * Log comando Telegram
     */
    public function logTelegramCommand($chatId, $username, $command, $success = true, $executionTime = 0, $error = null)
    {
        $context = [
            'chat_id' => $chatId,
            'username' => $username,
            'command' => $command,
            'success' => $success,
            'execution_time' => $executionTime
        ];

        if ($error) {
            $context['error'] = $error;
        }

        $level = $success ? 'INFO' : 'ERROR';
        $message = $success 
            ? "Command executed: $command by $username" 
            : "Command failed: $command by $username - $error";

        $this->log($level, $message, $context);
    }

    /**
     * Log API call a LibreNMS
     */
    public function logApiCall($endpoint, $method, $responseCode, $executionTime, $error = null)
    {
        $context = [
            'endpoint' => $endpoint,
            'method' => $method,
            'response_code' => $responseCode,
            'execution_time' => $executionTime
        ];

        if ($error) {
            $context['error'] = $error;
        }

        $level = ($responseCode >= 200 && $responseCode < 300) ? 'INFO' : 'ERROR';
        $message = "LibreNMS API call: $method $endpoint ($responseCode)";

        $this->log($level, $message, $context);
    }

    /**
     * Rotazione log
     */
    private function rotateLogIfNeeded()
    {
        if (!file_exists($this->logFile)) {
            return;
        }

        if (filesize($this->logFile) < $this->maxLogSize) {
            return;
        }

        // Rinomina i file esistenti
        for ($i = $this->maxLogFiles - 1; $i >= 1; $i--) {
            $oldFile = $this->logFile . '.' . $i;
            $newFile = $this->logFile . '.' . ($i + 1);
            
            if (file_exists($oldFile)) {
                if ($i == $this->maxLogFiles - 1) {
                    unlink($oldFile);
                } else {
                    rename($oldFile, $newFile);
                }
            }
        }

        // Rinomina il file corrente
        rename($this->logFile, $this->logFile . '.1');
    }

    /**
     * Ottieni ultime N righe del log
     */
    public function getTailLines($lines = 50)
    {
        if (!file_exists($this->logFile)) {
            return "Nessun log disponibile.";
        }

        $content = file_get_contents($this->logFile);
        $logLines = explode("\n", trim($content));
        
        $tailLines = array_slice($logLines, -$lines);
        $formattedLines = [];

        foreach ($tailLines as $line) {
            if (empty($line)) continue;
            
            $logEntry = json_decode($line, true);
            if ($logEntry) {
                $timestamp = $logEntry['timestamp'] ?? 'N/A';
                $level = $logEntry['level'] ?? 'INFO';
                $message = $logEntry['message'] ?? '';
                
                $formattedLines[] = "[$timestamp] [$level] $message";
            } else {
                $formattedLines[] = $line;
            }
        }

        return implode("\n", $formattedLines);
    }

    /**
     * Ottieni statistiche log
     */
    public function getLogStats($hours = 24)
    {
        if (!file_exists($this->logFile)) {
            return ['total' => 0, 'by_level' => []];
        }

        $sinceTime = time() - ($hours * 3600);
        $content = file_get_contents($this->logFile);
        $logLines = explode("\n", trim($content));
        
        $stats = [
            'total' => 0,
            'by_level' => [
                'DEBUG' => 0,
                'INFO' => 0,
                'WARNING' => 0,
                'ERROR' => 0
            ]
        ];

        foreach ($logLines as $line) {
            if (empty($line)) continue;
            
            $logEntry = json_decode($line, true);
            if (!$logEntry) continue;
            
            $timestamp = strtotime($logEntry['timestamp'] ?? '');
            if ($timestamp < $sinceTime) continue;
            
            $stats['total']++;
            $level = $logEntry['level'] ?? 'INFO';
            if (isset($stats['by_level'][$level])) {
                $stats['by_level'][$level]++;
            }
        }

        return $stats;
    }

    /**
     * Cerca nel log
     */
    public function searchLogs($query, $maxResults = 100)
    {
        if (!file_exists($this->logFile)) {
            return [];
        }

        $content = file_get_contents($this->logFile);
        $logLines = explode("\n", trim($content));
        $results = [];
        $count = 0;

        foreach (array_reverse($logLines) as $line) {
            if (empty($line) || $count >= $maxResults) break;
            
            if (stripos($line, $query) !== false) {
                $logEntry = json_decode($line, true);
                if ($logEntry) {
                    $results[] = $logEntry;
                    $count++;
                }
            }
        }

        return $results;
    }

    /**
     * Pulisci log vecchi
     */
    public function cleanOldLogs($days = 30)
    {
        $logDir = dirname($this->logFile);
        $cutoffTime = time() - ($days * 86400);
        $cleaned = 0;

        if (is_dir($logDir)) {
            $files = glob($logDir . '/*.log*');
            foreach ($files as $file) {
                if (filemtime($file) < $cutoffTime) {
                    unlink($file);
                    $cleaned++;
                }
            }
        }

        $this->info("Cleaned $cleaned old log files older than $days days");
        return $cleaned;
    }
}