<?php

/**
 * AlertTracker - Tracking e statistiche alert reali
 * 
 * @author Paolo Trivisonno
 * @version 2.0
 */
namespace LibreBot\Lib;

use PDO;

class AlertTracker
{
    private PDO $db;
    private Logger $logger;

    public function __construct(PDO $db, Logger $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->initializeTables();
    }

    /**
     * Inizializza tabelle per tracking
     */
    private function initializeTables(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS alert_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                alert_id INTEGER UNIQUE,
                device_id INTEGER,
                rule_name TEXT,
                severity TEXT DEFAULT 'warning',
                state INTEGER,
                first_seen INTEGER,
                last_seen INTEGER,
                occurrence_count INTEGER DEFAULT 1,
                acknowledged_by TEXT,
                acknowledged_at INTEGER
            )
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS bot_stats (
                key TEXT PRIMARY KEY,
                value TEXT,
                updated_at INTEGER
            )
        ");

        $this->db->exec("
            CREATE INDEX IF NOT EXISTS idx_alert_history_rule ON alert_history(rule_name)
        ");

        $this->db->exec("
            CREATE INDEX IF NOT EXISTS idx_alert_history_last_seen ON alert_history(last_seen)
        ");
    }

    /**
     * Traccia un alert (inserisce o aggiorna contatore)
     */
    public function trackAlert(array $alertData): void
    {
        $alertId = $alertData['id'] ?? null;
        if (!$alertId) {
            return;
        }

        $now = time();
        $deviceId = $alertData['device_id'] ?? null;
        $ruleName = $alertData['rule']['name'] ?? $alertData['rule_name'] ?? 'Unknown';
        $severity = $alertData['severity'] ?? 'warning';
        $state = $alertData['state'] ?? 1;

        // Verifica se esiste gia
        $stmt = $this->db->prepare("SELECT id, occurrence_count FROM alert_history WHERE alert_id = ?");
        $stmt->execute([$alertId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Aggiorna last_seen e incrementa counter
            $stmt = $this->db->prepare("
                UPDATE alert_history 
                SET last_seen = ?, occurrence_count = occurrence_count + 1, state = ?
                WHERE alert_id = ?
            ");
            $stmt->execute([$now, $state, $alertId]);
        } else {
            // Inserisci nuovo
            $stmt = $this->db->prepare("
                INSERT INTO alert_history 
                (alert_id, device_id, rule_name, severity, state, first_seen, last_seen, occurrence_count)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([$alertId, $deviceId, $ruleName, $severity, $state, $now, $now]);
        }

        $this->logger->debug("Alert tracked", ['alert_id' => $alertId, 'rule_name' => $ruleName]);
    }

    /**
     * Traccia multipli alert
     */
    public function trackAlerts(array $alerts): void
    {
        foreach ($alerts as $alert) {
            $this->trackAlert($alert);
        }
    }

    /**
     * Registra acknowledgment
     */
    public function setAcknowledged(int $alertId, string $username): void
    {
        $now = time();
        $stmt = $this->db->prepare("
            UPDATE alert_history 
            SET acknowledged_by = ?, acknowledged_at = ?, state = 0
            WHERE alert_id = ?
        ");
        $stmt->execute([$username, $now, $alertId]);

        $this->logger->info("Alert acknowledged in tracker", [
            'alert_id' => $alertId,
            'acknowledged_by' => $username
        ]);
    }

    /**
     * Ottieni top alert per tipo (sostituisce il mock)
     */
    public function getTopAlertTypes(int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT rule_name, SUM(occurrence_count) as total
            FROM alert_history
            WHERE rule_name IS NOT NULL AND rule_name != ''
            GROUP BY rule_name
            ORDER BY total DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[$row['rule_name']] = (int)$row['total'];
        }
        
        return $results;
    }

    /**
     * Ottieni statistiche alert per periodo
     */
    public function getAlertStats(int $hours = 24): array
    {
        $since = time() - ($hours * 3600);
        
        // Totale alert nel periodo
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total, SUM(occurrence_count) as occurrences
            FROM alert_history
            WHERE last_seen >= ?
        ");
        $stmt->execute([$since]);
        $totals = $stmt->fetch(PDO::FETCH_ASSOC);

        // Per severity
        $stmt = $this->db->prepare("
            SELECT severity, COUNT(*) as count
            FROM alert_history
            WHERE last_seen >= ?
            GROUP BY severity
        ");
        $stmt->execute([$since]);
        $bySeverity = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $bySeverity[$row['severity']] = (int)$row['count'];
        }

        // Acknowledged vs non-acknowledged
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(CASE WHEN acknowledged_at IS NOT NULL THEN 1 END) as acknowledged,
                COUNT(CASE WHEN acknowledged_at IS NULL AND state = 1 THEN 1 END) as pending
            FROM alert_history
            WHERE last_seen >= ?
        ");
        $stmt->execute([$since]);
        $ackStats = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total' => (int)($totals['total'] ?? 0),
            'occurrences' => (int)($totals['occurrences'] ?? 0),
            'by_severity' => $bySeverity,
            'acknowledged' => (int)($ackStats['acknowledged'] ?? 0),
            'pending' => (int)($ackStats['pending'] ?? 0)
        ];
    }

    /**
     * Ottieni storico alert per dispositivo
     */
    public function getDeviceAlertHistory(int $deviceId, int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM alert_history
            WHERE device_id = ?
            ORDER BY last_seen DESC
            LIMIT ?
        ");
        $stmt->execute([$deviceId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Salva stat bot
     */
    public function setBotStat(string $key, string $value): void
    {
        $now = time();
        $stmt = $this->db->prepare("
            INSERT OR REPLACE INTO bot_stats (key, value, updated_at)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$key, $value, $now]);
    }

    /**
     * Ottieni stat bot
     */
    public function getBotStat(string $key, ?string $default = null): ?string
    {
        $stmt = $this->db->prepare("SELECT value FROM bot_stats WHERE key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : $default;
    }

    /**
     * Pulisci alert vecchi
     */
    public function cleanOldAlerts(int $days = 90): int
    {
        $cutoff = time() - ($days * 86400);
        $stmt = $this->db->prepare("DELETE FROM alert_history WHERE last_seen < ?");
        $stmt->execute([$cutoff]);
        $deleted = $stmt->rowCount();
        
        if ($deleted > 0) {
            $this->logger->info("Cleaned old alerts", ['deleted' => $deleted, 'older_than_days' => $days]);
        }
        
        return $deleted;
    }
}
