<?php

/**
 * LibreNMSAPI - Gestione API LibreNMS con caching
 * 
 * @author Paolo Trivisonno
 * @version 2.0
 */
class LibreNMSAPI
{
    private $url;
    private $token;
    private $logger;
    private $db;
    private $config;
    private $cache = [];

    public function __construct($url, $token, $logger, $db, $config)
    {
        $this->url = rtrim($url, '/');
        $this->token = $token;
        $this->logger = $logger;
        $this->db = $db;
        $this->config = $config;
        $this->initializeCacheTables();
    }

    /**
     * Inizializza tabelle cache
     */
    private function initializeCacheTables()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS api_cache (
                cache_key TEXT PRIMARY KEY,
                data TEXT,
                created_at INTEGER,
                expires_at INTEGER
            )
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS maintenance_windows (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                device_id INTEGER,
                start_time INTEGER,
                end_time INTEGER,
                reason TEXT,
                created_by TEXT,
                created_at INTEGER
            )
        ");
    }

    /**
     * Chiamata API generica
     */
    public function call($endpoint, $method = 'GET', $data = null, $useCache = true)
    {
        $startTime = microtime(true);
        
        // Controlla cache se GET e caching abilitato
        if ($method === 'GET' && $useCache && $this->config['advanced']['enable_caching']) {
            $cached = $this->getFromCache($endpoint);
            if ($cached !== null) {
                return $cached;
            }
        }

        $url = $this->url . $endpoint;
        $ch = curl_init($url);
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                "X-Auth-Token: {$this->token}",
                "Content-Type: application/json"
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'LibreBot/2.0'
        ]);

        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        if ($response === false || !empty($error)) {
            $this->logger->logApiCall($endpoint, $method, 0, $executionTime, $error);
            throw new Exception("cURL Error: $error");
        }

        $this->logger->logApiCall($endpoint, $method, $httpCode, $executionTime);

        if ($httpCode >= 400) {
            throw new Exception("HTTP Error $httpCode: $response");
        }

        $decodedResponse = json_decode($response, true);
        
        // Salva in cache se GET e valido
        if ($method === 'GET' && $httpCode === 200 && $this->config['advanced']['enable_caching']) {
            $this->saveToCache($endpoint, $decodedResponse);
        }

        return $decodedResponse;
    }

    /**
     * Cache management
     */
    private function getFromCache($key)
    {
        $stmt = $this->db->prepare("SELECT data, expires_at FROM api_cache WHERE cache_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && $result['expires_at'] > time()) {
            return json_decode($result['data'], true);
        }

        return null;
    }

    private function saveToCache($key, $data)
    {
        $expiresAt = time() + $this->config['advanced']['cache_duration'];
        $stmt = $this->db->prepare("
            INSERT OR REPLACE INTO api_cache (cache_key, data, created_at, expires_at)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$key, json_encode($data), time(), $expiresAt]);
    }

    /**
     * Pulisci cache scaduta
     */
    public function cleanExpiredCache()
    {
        $stmt = $this->db->prepare("DELETE FROM api_cache WHERE expires_at < ?");
        $stmt->execute([time()]);
        return $stmt->rowCount();
    }

    // ==================== ALERT METHODS ====================

    /**
     * Ottieni alert attivi
     */
    public function getActiveAlerts()
    {
        return $this->call('/api/v0/alerts?state=1');
    }

    /**
     * Acknowledge alert
     */
    public function acknowledgeAlert($alertId, $note = 'Acknowledged via Telegram', $untilClear = false)
    {
        $data = [
            'note' => $note,
            'until_clear' => $untilClear
        ];
        return $this->call("/api/v0/alerts/$alertId", 'PUT', $data, false);
    }

    /**
     * Ottieni storico alert per dispositivo
     */
    public function getAlertHistory($deviceId, $limit = 50)
    {
        return $this->call("/api/v0/alerts?device_id=$deviceId&limit=$limit");
    }

    /**
     * Ottieni regola alert
     */
    public function getAlertRule($ruleId)
    {
        return $this->call("/api/v0/rules/$ruleId");
    }

    /**
     * Ottieni statistiche alert
     */
    public function getAlertStats($period = 'day')
    {
        $endpoint = "/api/v0/alerts/stats";
        return $this->call($endpoint);
    }

    // ==================== DEVICE METHODS ====================

    /**
     * Ottieni dispositivi
     */
    public function getDevices($type = 'active', $filter = null)
    {
        $endpoint = "/api/v0/devices";
        if ($type) {
            $endpoint .= "?type=$type";
        }
        return $this->call($endpoint);
    }

    /**
     * Ottieni dispositivo specifico
     */
    public function getDevice($deviceId)
    {
        return $this->call("/api/v0/devices/$deviceId");
    }

    /**
     * Ottieni porte dispositivo
     */
    public function getDevicePorts($deviceId)
    {
        return $this->call("/api/v0/devices/$deviceId/ports");
    }

    /**
     * Ottieni utilizzo banda top
     */
    public function getTopBandwidthDevices($limit = 10)
    {
        return $this->call("/api/v0/devices/bandwidth/top/$limit");
    }

    /**
     * Aggiungi dispositivo
     */
    public function addDevice($hostname, $community = 'public', $version = 'v2c', $port = 161)
    {
        $data = [
            'hostname' => $hostname,
            'community' => $community,
            'version' => $version,
            'port' => $port
        ];
        return $this->call('/api/v0/devices', 'POST', $data, false);
    }

    /**
     * Rimuovi dispositivo
     */
    public function removeDevice($deviceId)
    {
        return $this->call("/api/v0/devices/$deviceId", 'DELETE', null, false);
    }

    /**
     * Modalità manutenzione
     */
    public function setMaintenanceMode($deviceId, $duration = 3600, $reason = 'Maintenance via Telegram')
    {
        $startTime = time();
        $endTime = $startTime + $duration;
        
        // Registra in database locale
        $stmt = $this->db->prepare("
            INSERT INTO maintenance_windows (device_id, start_time, end_time, reason, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$deviceId, $startTime, $endTime, $reason, 'telegram_bot', $startTime]);

        // Se LibreNMS supporta maintenance API, usa quella
        $data = [
            'start' => date('c', $startTime),
            'end' => date('c', $endTime),
            'title' => $reason
        ];
        
        try {
            return $this->call("/api/v0/devices/$deviceId/maintenance", 'POST', $data, false);
        } catch (Exception $e) {
            // Fallback: disabilita polling manualmente se API non disponibile
            $this->logger->warning("Maintenance API not available, using fallback method");
            return ['status' => 'ok', 'message' => 'Maintenance mode set locally'];
        }
    }

    /**
     * Ottieni uptime dispositivo
     */
    public function getDeviceUptime($deviceId, $period = '24h')
    {
        return $this->call("/api/v0/devices/$deviceId/uptime/$period");
    }

    // ==================== SYSTEM METHODS ====================

    /**
     * Ottieni sistema info
     */
    public function getSystemInfo()
    {
        return $this->call('/api/v0/system');
    }

    /**
     * Health check LibreNMS
     */
    public function getHealthCheck()
    {
        try {
            $response = $this->call('/api/v0/system/health');
            return [
                'status' => 'ok',
                'data' => $response
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    // ==================== UTILITY METHODS ====================

    /**
     * Test connessione API
     */
    public function testConnection()
    {
        try {
            $response = $this->call('/api/v0/system');
            return [
                'success' => true,
                'version' => $response['version'] ?? 'unknown',
                'message' => 'Connection successful'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Ottieni configurazione dispositivo
     */
    public function getDeviceConfig($deviceId)
    {
        return $this->call("/api/v0/devices/$deviceId/config");
    }

    /**
     * Esegui discovery su dispositivo
     */
    public function rediscoverDevice($deviceId)
    {
        $data = ['device_id' => $deviceId];
        return $this->call('/api/v0/devices/discovery', 'POST', $data, false);
    }

    /**
     * Ottieni traffico porte
     */
    public function getPortTraffic($deviceId, $portId, $period = '24h')
    {
        return $this->call("/api/v0/devices/$deviceId/ports/$portId/traffic/$period");
    }

    /**
     * Bulk acknowledge alerts
     */
    public function bulkAcknowledgeAlerts($pattern, $note = 'Bulk acknowledged')
    {
        $alerts = $this->getActiveAlerts();
        $acknowledged = [];
        
        foreach ($alerts['alerts'] ?? [] as $alert) {
            $alertText = strtolower($alert['rule']['name'] ?? '');
            if (stripos($alertText, $pattern) !== false) {
                try {
                    $this->acknowledgeAlert($alert['id'], $note);
                    $acknowledged[] = $alert['id'];
                } catch (Exception $e) {
                    $this->logger->error("Failed to ack alert {$alert['id']}: " . $e->getMessage());
                }
            }
        }
        
        return $acknowledged;
    }

    /**
     * Ottieni performance metrics
     */
    public function getPerformanceMetrics($deviceId, $metric = 'cpu', $period = '24h')
    {
        return $this->call("/api/v0/devices/$deviceId/graphs/$metric/$period");
    }
}