<?php

/**
 * DeviceCommands - Gestione comandi dispositivi
 * 
 * @author Paolo Trivisonno
 * @version 2.0
 */
class DeviceCommands
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
     * Lista dispositivi con filtro
     */
    public function listDevices($filter = '')
    {
        try {
            $devices = $this->api->getDevices('active');
            $deviceList = $devices['devices'] ?? [];

            if (!empty($filter)) {
                $deviceList = array_filter($deviceList, function ($device) use ($filter) {
                    foreach (['hostname', 'sysName', 'os', 'display', 'type', 'ip'] as $field) {
                        if (isset($device[$field]) && stripos($device[$field], $filter) !== false) {
                            return true;
                        }
                    }
                    return false;
                });
            }

            if (empty($deviceList)) {
                return $filter ? "❌ Nessun dispositivo trovato con filtro '$filter'." : "❌ Nessun dispositivo trovato.";
            }

            $text = "📟 Dispositivi trovati (" . count($deviceList) . "):\n\n";

            foreach (array_slice($deviceList, 0, 20) as $device) {
                $text .= "🖥️ {$device['hostname']}";
                $text .= isset($device['sysName']) ? " ({$device['sysName']})" : '';
                $text .= "\n📶 Stato: " . ($device['status'] ?? 'unknown') . " | ID: {$device['device_id']}\n";
                
                if (!empty($device['ip'])) {
                    $text .= "🌐 IP: {$device['ip']}\n";
                }
                if (!empty($device['os'])) {
                    $text .= "🧠 OS: {$device['os']}\n";
                }
                $text .= "\n";
            }

            if (count($deviceList) > 20) {
                $text .= "... e altri " . (count($deviceList) - 20) . " dispositivi.\n";
                $text .= "Usa un filtro più specifico per limitare i risultati.";
            }

            return $text;

        } catch (Exception $e) {
            $this->logger->error("Error listing devices: " . $e->getMessage());
            return "❌ Errore nel recupero dei dispositivi: " . $e->getMessage();
        }
    }

    /**
     * Status dettagliato dispositivo
     */
    public function getDeviceStatus($deviceId)
    {
        try {
            $deviceData = $this->api->getDevice($deviceId);
            $device = $deviceData['devices'][0] ?? null;

            if (!$device) {
                return "❌ Dispositivo $deviceId non trovato.";
            }

            $text = "📊 Status Dispositivo\n\n";
            $text .= "🖥️ Host: {$device['hostname']}\n";
            $text .= "📋 Display: " . ($device['display'] ?? 'N/D') . "\n";
            $text .= "🌐 IP: " . ($device['ip'] ?? 'N/D') . "\n";
            $text .= "🧠 OS: " . ($device['os'] ?? 'N/D') . "\n";
            $text .= "📶 Status: " . ($device['status'] ? '🟢 Online' : '🔴 Offline') . "\n";
            $text .= "⏰ Ultimo poll: " . ($device['last_polled'] ?? 'N/D') . "\n";
            
            if (isset($device['uptime'])) {
                $uptimeHours = round($device['uptime'] / 3600, 1);
                $text .= "⏳ Uptime: {$uptimeHours}h\n";
            }

            // Aggiungi info porte se disponibili
            try {
                $ports = $this->api->getDevicePorts($deviceId);
                $totalPorts = count($ports['ports'] ?? []);
                $activePorts = count(array_filter($ports['ports'] ?? [], function($port) {
                    return $port['ifOperStatus'] === 'up';
                }));
                $text .= "🔌 Porte: $activePorts/$totalPorts attive\n";
            } catch (Exception $e) {
                // Non critico se non riusciamo a ottenere le porte
            }

            // Aggiungi alert attivi per questo dispositivo
            try {
                $alerts = $this->api->getActiveAlerts();
                $deviceAlerts = array_filter($alerts['alerts'] ?? [], function($alert) use ($deviceId) {
                    return $alert['device_id'] == $deviceId;
                });
                $alertCount = count($deviceAlerts);
                $text .= "⚠️ Alert attivi: $alertCount\n";
            } catch (Exception $e) {
                // Non critico
            }

            return $text;

        } catch (Exception $e) {
            $this->logger->error("Error getting device status: " . $e->getMessage());
            return "❌ Errore nel recupero dello status: " . $e->getMessage();
        }
    }

    /**
     * Status porta specifica
     */
    public function getPortStatus($deviceId, $portName)
    {
        try {
            $ports = $this->api->getDevicePorts($deviceId);
            $port = null;

            foreach ($ports['ports'] ?? [] as $p) {
                if (stripos($p['ifName'] ?? '', $portName) !== false || 
                    stripos($p['ifDescr'] ?? '', $portName) !== false ||
                    $p['port_id'] == $portName) {
                    $port = $p;
                    break;
                }
            }

            if (!$port) {
                return "❌ Porta '$portName' non trovata sul dispositivo $deviceId.";
            }

            $text = "🔌 Status Porta\n\n";
            $text .= "📋 Nome: " . ($port['ifName'] ?? 'N/D') . "\n";
            $text .= "📝 Descrizione: " . ($port['ifDescr'] ?? 'N/D') . "\n";
            $text .= "📶 Status Admin: " . ($port['ifAdminStatus'] ?? 'N/D') . "\n";
            $text .= "🔗 Status Operativo: " . ($port['ifOperStatus'] ?? 'N/D') . "\n";
            $text .= "⚡ Velocità: " . ($port['ifSpeed'] ?? 'N/D') . " bps\n";
            
            if (isset($port['ifInOctets_rate'])) {
                $inMbps = round(($port['ifInOctets_rate'] * 8) / 1000000, 2);
                $text .= "⬇️ Traffic IN: {$inMbps} Mbps\n";
            }
            
            if (isset($port['ifOutOctets_rate'])) {
                $outMbps = round(($port['ifOutOctets_rate'] * 8) / 1000000, 2);
                $text .= "⬆️ Traffic OUT: {$outMbps} Mbps\n";
            }

            return $text;

        } catch (Exception $e) {
            $this->logger->error("Error getting port status: " . $e->getMessage());
            return "❌ Errore nel recupero dello status porta: " . $e->getMessage();
        }
    }

    /**
     * Top dispositivi per utilizzo banda
     */
    public function getTopBandwidth($limit = 10)
    {
        try {
            $topDevices = $this->api->getTopBandwidthDevices($limit);
            
            if (empty($topDevices)) {
                return "ℹ️ Nessun dato di banda disponibile.";
            }

            $text = "🌐 Top $limit utilizzo banda:\n\n";
            $position = 1;

            foreach ($topDevices as $device) {
                $hostname = $device['hostname'] ?? 'N/D';
                $totalMbps = isset($device['total_bps']) ? round($device['total_bps'] / 1000000, 2) : 0;
                
                $text .= "$position. $hostname - {$totalMbps} Mbps\n";
                $position++;
            }

            return $text;

        } catch (Exception $e) {
            $this->logger->error("Error getting bandwidth stats: " . $e->getMessage());
            return "❌ Errore nel recupero statistiche banda: " . $e->getMessage();
        }
    }

    /**
     * Aggiungi dispositivo
     */
    public function addDevice($hostname, $community = 'public', $username = '')
    {
        try {
            $result = $this->api->addDevice($hostname, $community);
            
            $this->logger->info("Device added by $username", [
                'hostname' => $hostname,
                'community' => $community,
                'added_by' => $username
            ]);

            return "✅ Dispositivo $hostname aggiunto con successo!\nCommunity: $community";

        } catch (Exception $e) {
            $this->logger->error("Error adding device: " . $e->getMessage());
            return "❌ Errore nell'aggiunta del dispositivo: " . $e->getMessage();
        }
    }

    /**
     * Rimuovi dispositivo
     */
    public function removeDevice($deviceId, $username = '')
    {
        try {
            // Ottieni info dispositivo prima di rimuoverlo
            $deviceData = $this->api->getDevice($deviceId);
            $device = $deviceData['devices'][0] ?? null;
            $hostname = $device['hostname'] ?? "ID:$deviceId";

            $result = $this->api->removeDevice($deviceId);
            
            $this->logger->warning("Device removed by $username", [
                'device_id' => $deviceId,
                'hostname' => $hostname,
                'removed_by' => $username
            ]);

            return "✅ Dispositivo $hostname (ID: $deviceId) rimosso con successo!";

        } catch (Exception $e) {
            $this->logger->error("Error removing device: " . $e->getMessage());
            return "❌ Errore nella rimozione del dispositivo: " . $e->getMessage();
        }
    }

    /**
     * Ri-discovery dispositivo
     */
    public function rediscoverDevice($deviceId, $username = '')
    {
        try {
            $result = $this->api->rediscoverDevice($deviceId);
            
            $this->logger->info("Device rediscovery initiated by $username", [
                'device_id' => $deviceId,
                'initiated_by' => $username
            ]);

            return "✅ Ri-discovery avviato per dispositivo $deviceId";

        } catch (Exception $e) {
            $this->logger->error("Error rediscovering device: " . $e->getMessage());
            return "❌ Errore nel ri-discovery: " . $e->getMessage();
        }
    }

    /**
     * Modalità manutenzione
     */
    public function setMaintenanceMode($deviceId, $action, $duration = 3600, $reason = 'Maintenance via Telegram', $username = '')
    {
        try {
            if ($action === 'on') {
                $result = $this->api->setMaintenanceMode($deviceId, $duration, $reason);
                
                $this->logger->info("Maintenance mode enabled by $username", [
                    'device_id' => $deviceId,
                    'duration' => $duration,
                    'reason' => $reason,
                    'enabled_by' => $username
                ]);

                $hours = round($duration / 3600, 1);
                return "🔧 Modalità manutenzione attivata per dispositivo $deviceId\nDurata: {$hours}h\nMotivo: $reason";
                
            } else {
                // Per disabilitare la manutenzione, dovremmo implementare l'API corrispondente
                return "ℹ️ Disattivazione manutenzione non ancora implementata";
            }

        } catch (Exception $e) {
            $this->logger->error("Error setting maintenance mode: " . $e->getMessage());
            return "❌ Errore nell'impostazione modalità manutenzione: " . $e->getMessage();
        }
    }

    /**
     * Performance report dispositivo
     */
    public function getPerformanceReport($deviceId, $period = '24h')
    {
        try {
            $device = $this->api->getDevice($deviceId);
            $deviceInfo = $device['devices'][0] ?? null;
            
            if (!$deviceInfo) {
                return "❌ Dispositivo $deviceId non trovato.";
            }

            $hostname = $deviceInfo['hostname'] ?? 'N/D';
            $text = "📈 Performance Report - $hostname\n";
            $text .= "📅 Periodo: $period\n\n";

            // CPU
            try {
                $cpu = $this->api->getPerformanceMetrics($deviceId, 'cpu', $period);
                if (isset($cpu['average'])) {
                    $text .= "🧠 CPU medio: " . round($cpu['average'], 1) . "%\n";
                }
            } catch (Exception $e) {
                $text .= "🧠 CPU: Dati non disponibili\n";
            }

            // Memory
            try {
                $memory = $this->api->getPerformanceMetrics($deviceId, 'memory', $period);
                if (isset($memory['average'])) {
                    $text .= "💾 Memory medio: " . round($memory['average'], 1) . "%\n";
                }
            } catch (Exception $e) {
                $text .= "💾 Memory: Dati non disponibili\n";
            }

            // Uptime
            try {
                $uptime = $this->api->getDeviceUptime($deviceId, $period);
                if (isset($uptime['percentage'])) {
                    $text .= "⏰ Uptime: " . round($uptime['percentage'], 2) . "%\n";
                }
            } catch (Exception $e) {
                $text .= "⏰ Uptime: Dati non disponibili\n";
            }

            return $text;

        } catch (Exception $e) {
            $this->logger->error("Error getting performance report: " . $e->getMessage());
            return "❌ Errore nel recupero del report: " . $e->getMessage();
        }
    }

    /**
     * Dashboard dispositivi
     */
    public function getDeviceDashboard()
    {
        try {
            $devices = $this->api->getDevices('active');
            $deviceList = $devices['devices'] ?? [];
            
            $total = count($deviceList);
            $online = count(array_filter($deviceList, function($d) { return $d['status'] ?? false; }));
            $offline = $total - $online;
            
            $text = "📊 Dashboard Dispositivi\n\n";
            $text .= "📟 Totale: $total\n";
            $text .= "🟢 Online: $online\n";
            $text .= "🔴 Offline: $offline\n";
            
            if ($total > 0) {
                $uptime = round(($online / $total) * 100, 1);
                $text .= "📊 Disponibilità: {$uptime}%\n";
            }

            // Aggiungi breakdown per OS
            $osCounts = [];
            foreach ($deviceList as $device) {
                $os = $device['os'] ?? 'Unknown';
                $osCounts[$os] = ($osCounts[$os] ?? 0) + 1;
            }
            
            if (!empty($osCounts)) {
                $text .= "\n🧠 Per OS:\n";
                arsort($osCounts);
                foreach (array_slice($osCounts, 0, 5, true) as $os => $count) {
                    $text .= "• $os: $count\n";
                }
            }

            return $text;

        } catch (Exception $e) {
            $this->logger->error("Error getting device dashboard: " . $e->getMessage());
            return "❌ Errore nel recupero del dashboard: " . $e->getMessage();
        }
    }
}