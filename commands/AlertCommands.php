<?php

/**
 * AlertCommands - Gestione comandi alert
 * 
 * @author Paolo Trivisonno
 * @version 2.0
 */
class AlertCommands
{
    private $api;
    private $logger;
    private $security;

    public function __construct($api, $logger, $security)
    {
        $this->api = $api;
        $this->logger = $logger;
        $this->security = $security;
    }

    /**
     * Lista alert attivi
     */
    public function listAlerts($chatId, $threadId)
    {
        try {
            $alerts = $this->api->getActiveAlerts();
            $alertList = $alerts['alerts'] ?? [];
            
            if (empty($alertList)) {
                return "✅ Nessun alert attivo.";
            }

            $text = "⚠️ Alert attivi (" . count($alertList) . "):\n\n";
            $alertCount = count($alertList);

            foreach ($alertList as $index => $alert) {
                $id = $alert['id'];
                $timestamp = $alert['timestamp'] ?? 'n/d';
                $deviceId = $alert['device_id'] ?? null;
                $ruleId = $alert['rule_id'] ?? null;

                // Info regola
                $ruleName = 'N/D';
                if ($ruleId) {
                    try {
                        $ruleData = $this->api->getAlertRule($ruleId);
                        $ruleName = $ruleData['rules'][0]['name'] ?? $ruleName;
                    } catch (Exception $e) {
                        $this->logger->warning("Failed to get rule $ruleId: " . $e->getMessage());
                    }
                }

                // Info dispositivo
                $hostname = 'sconosciuto';
                $display = 'n/a';
                $ip = 'n/a';
                if ($deviceId) {
                    try {
                        $deviceData = $this->api->getDevice($deviceId);
                        $device = $deviceData['devices'][0] ?? null;
                        if ($device) {
                            $hostname = $device['hostname'] ?? $hostname;
                            $display = $device['display'] ?? $display;
                            $ip = $device['ip'] ?? $ip;
                        }
                    } catch (Exception $e) {
                        $this->logger->warning("Failed to get device $deviceId: " . $e->getMessage());
                    }
                }

                $text .= "🆔 $id | 📅 $timestamp\n";
                $text .= "💥 Tipo: $ruleName\n";
                $text .= "🖥️ Host: $hostname\n";
                $text .= "📋 Display: $display\n";
                $text .= "🌐 IP: $ip\n";
                
                if ($alertCount > 1 && $index < $alertCount - 1) {
                    $text .= "-------------------------\n";
                }
            }

            return $text;

        } catch (Exception $e) {
            $this->logger->error("Error listing alerts: " . $e->getMessage());
            return "❌ Errore nel recupero degli alert: " . $e->getMessage();
        }
    }

    /**
     * Acknowledge alert
     */
    public function acknowledgeAlert($alertId, $note, $chatId, $username)
    {
        try {
            $response = $this->api->acknowledgeAlert($alertId, $note);
            
            $this->logger->info("Alert $alertId acknowledged by $username", [
                'alert_id' => $alertId,
                'note' => $note,
                'user' => $username
            ]);

            return "✅ ACK eseguito su alert $alertId\nNota: $note";

        } catch (Exception $e) {
            $this->logger->error("Failed to acknowledge alert $alertId: " . $e->getMessage());
            return "❌ Errore ACK alert $alertId: " . $e->getMessage();
        }
    }

    /**
     * Statistiche alert
     */
    public function getAlertStats($period = 'today')
    {
        try {
            $stats = $this->api->getAlertStats($period);
            $activeAlerts = $this->api->getActiveAlerts();
            $activeCount = count($activeAlerts['alerts'] ?? []);

            $text = "📊 Statistiche Alert\n\n";
            $text .= "🔴 Alert attivi: $activeCount\n";
            
            if (isset($stats['total'])) {
                $text .= "📈 Totale nel periodo: {$stats['total']}\n";
            }
            
            if (isset($stats['by_severity'])) {
                $text .= "\n📋 Per severity:\n";
                foreach ($stats['by_severity'] as $severity => $count) {
                    $emoji = $this->getSeverityEmoji($severity);
                    $text .= "$emoji $severity: $count\n";
                }
            }

            // Aggiungi top alert più frequenti
            $topAlerts = $this->getTopAlertTypes();
            if (!empty($topAlerts)) {
                $text .= "\n🔥 Top alert types:\n";
                foreach (array_slice($topAlerts, 0, 5) as $alertType => $count) {
                    $text .= "• $alertType: $count\n";
                }
            }

            return $text;

        } catch (Exception $e) {
            $this->logger->error("Error getting alert stats: " . $e->getMessage());
            return "❌ Errore nel recupero delle statistiche: " . $e->getMessage();
        }
    }

    /**
     * Storico alert per dispositivo
     */
    public function getAlertHistory($deviceId, $limit = 10)
    {
        try {
            $history = $this->api->getAlertHistory($deviceId, $limit);
            $alerts = $history['alerts'] ?? [];

            if (empty($alerts)) {
                return "✅ Nessun alert storico per il dispositivo $deviceId.";
            }

            $text = "📜 Storico alert per dispositivo $deviceId:\n\n";
            
            foreach (array_slice($alerts, 0, $limit) as $alert) {
                $timestamp = $alert['timestamp'] ?? 'n/d';
                $state = $alert['state'] ?? 'unknown';
                $ruleName = $alert['rule']['name'] ?? 'N/D';
                
                $stateEmoji = $state == 1 ? '🔴' : '✅';
                $text .= "$stateEmoji [$timestamp] $ruleName\n";
            }

            return $text;

        } catch (Exception $e) {
            $this->logger->error("Error getting alert history: " . $e->getMessage());
            return "❌ Errore nel recupero dello storico: " . $e->getMessage();
        }
    }

    /**
     * Bulk acknowledge con pattern
     */
    public function bulkAcknowledge($pattern, $note, $username)
    {
        try {
            $acknowledged = $this->api->bulkAcknowledgeAlerts($pattern, $note);
            
            if (empty($acknowledged)) {
                return "ℹ️ Nessun alert trovato con pattern '$pattern'";
            }

            $count = count($acknowledged);
            $alertIds = implode(', ', $acknowledged);
            
            $this->logger->info("Bulk acknowledge by $username", [
                'pattern' => $pattern,
                'count' => $count,
                'alert_ids' => $acknowledged
            ]);

            return "✅ $count alert riconosciuti con pattern '$pattern'\nIDs: $alertIds\nNota: $note";

        } catch (Exception $e) {
            $this->logger->error("Error in bulk acknowledge: " . $e->getMessage());
            return "❌ Errore nel bulk acknowledge: " . $e->getMessage();
        }
    }

    /**
     * Top alert più frequenti
     */
    public function getTopAlerts($limit = 10)
    {
        try {
            $topAlerts = $this->getTopAlertTypes($limit);
            
            if (empty($topAlerts)) {
                return "ℹ️ Nessun dato disponibile per top alert.";
            }

            $text = "🔥 Top $limit alert più frequenti:\n\n";
            $position = 1;
            
            foreach ($topAlerts as $alertType => $count) {
                $text .= "$position. $alertType ($count volte)\n";
                $position++;
            }

            return $text;

        } catch (Exception $e) {
            $this->logger->error("Error getting top alerts: " . $e->getMessage());
            return "❌ Errore nel recupero dei top alert: " . $e->getMessage();
        }
    }

    /**
     * Escalation alert
     */
    public function escalateAlert($alertId, $reason, $username)
    {
        global $notifications;
        
        try {
            // Ottieni info alert
            $alerts = $this->api->getActiveAlerts();
            $alert = null;
            
            foreach ($alerts['alerts'] ?? [] as $a) {
                if ($a['id'] == $alertId) {
                    $alert = $a;
                    break;
                }
            }

            if (!$alert) {
                return "❌ Alert $alertId non trovato o non più attivo.";
            }

            // Log escalation
            $this->logger->warning("Alert escalated", [
                'alert_id' => $alertId,
                'escalated_by' => $username,
                'reason' => $reason,
                'rule_name' => $alert['rule']['name'] ?? 'N/D'
            ]);

            // Invia notifica ai contatti di emergenza se configurati
            if (!empty($notifications['emergency_contacts'])) {
                // TODO: Implementare invio SMS/email
            }

            return "🚨 Alert $alertId escalato con successo\nMotivo: $reason\nEscalato da: $username";

        } catch (Exception $e) {
            $this->logger->error("Error escalating alert: " . $e->getMessage());
            return "❌ Errore nell'escalation: " . $e->getMessage();
        }
    }

    // ==================== UTILITY METHODS ====================

    /**
     * Ottieni emoji per severity
     */
    private function getSeverityEmoji($severity)
    {
        $emojis = [
            'critical' => '🔴',
            'warning' => '🟠',
            'ok' => '🟢',
            'info' => 'ℹ️'
        ];
        
        return $emojis[strtolower($severity)] ?? '⚪';
    }

    /**
     * Calcola top alert types dal database locale
     */
    private function getTopAlertTypes($limit = 10)
    {
        // Questa funzione richiederebbe un tracking locale degli alert
        // Per ora ritorna un mock - implementare con database
        return [
            'Device Down' => 15,
            'High CPU Usage' => 12,
            'Interface Down' => 8,
            'High Memory Usage' => 6,
            'Disk Space Low' => 4
        ];
    }
}