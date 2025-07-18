<?php

/**
 * NetworkCommands - Comandi troubleshooting di rete
 * 
 * @author Paolo Trivisonno
 * @version 2.0
 */
class NetworkCommands
{
    private $logger;
    private $security;
    private $config;

    public function __construct($logger, $security, $config)
    {
        $this->logger = $logger;
        $this->security = $security;
        $this->config = $config;
    }

    /**
     * Ping
     */
    public function ping($host, $username = '')
    {
        $validatedHost = $this->security->validateShellInput($host, 'host');
        if (!$validatedHost) {
            return "❌ Host non valido o non autorizzato: $host";
        }

        try {
            $startTime = microtime(true);
            $output = $this->executeShellCommand("ping -c 5 -W 2 " . escapeshellarg($validatedHost));
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->logTelegramCommand(0, $username, "ping $host", true, $executionTime);

            return "📡 Ping verso $validatedHost:\n\n```\n$output\n```";

        } catch (Exception $e) {
            $this->logger->error("Ping command failed: " . $e->getMessage());
            return "❌ Errore durante il ping: " . $e->getMessage();
        }
    }

    /**
     * Traceroute
     */
    public function traceroute($host, $username = '')
    {
        $validatedHost = $this->security->validateShellInput($host, 'host');
        if (!$validatedHost) {
            return "❌ Host non valido o non autorizzato: $host";
        }

        try {
            $startTime = microtime(true);
            $output = $this->executeShellCommand("traceroute " . escapeshellarg($validatedHost));
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->logTelegramCommand(0, $username, "traceroute $host", true, $executionTime);

            return "🌍 Traceroute verso $validatedHost:\n\n```\n$output\n```";

        } catch (Exception $e) {
            $this->logger->error("Traceroute command failed: " . $e->getMessage());
            return "❌ Errore durante il traceroute: " . $e->getMessage();
        }
    }

    /**
     * MTR (My Traceroute)
     */
    public function mtr($host, $count = 10, $username = '')
    {
        $validatedHost = $this->security->validateShellInput($host, 'host');
        if (!$validatedHost) {
            return "❌ Host non valido o non autorizzato: $host";
        }

        if (!$this->isCommandAvailable('mtr')) {
            return "❌ Comando MTR non disponibile sul sistema";
        }

        try {
            $startTime = microtime(true);
            $output = $this->executeShellCommand("mtr -r -c $count " . escapeshellarg($validatedHost));
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->logTelegramCommand(0, $username, "mtr $host", true, $executionTime);

            return "🔄 MTR verso $validatedHost ($count cicli):\n\n```\n$output\n```";

        } catch (Exception $e) {
            $this->logger->error("MTR command failed: " . $e->getMessage());
            return "❌ Errore durante MTR: " . $e->getMessage();
        }
    }

    /**
     * NSLookup
     */
    public function nslookup($host, $username = '')
    {
        $validatedHost = $this->security->validateShellInput($host, 'domain');
        if (!$validatedHost) {
            return "❌ Host non valido: $host";
        }

        try {
            $startTime = microtime(true);
            $output = $this->executeShellCommand("nslookup " . escapeshellarg($validatedHost));
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->logTelegramCommand(0, $username, "nslookup $host", true, $executionTime);

            return "🔎 NSLookup per $validatedHost:\n\n```\n$output\n```";

        } catch (Exception $e) {
            $this->logger->error("NSLookup command failed: " . $e->getMessage());
            return "❌ Errore durante nslookup: " . $e->getMessage();
        }
    }

    /**
     * DIG (Domain Information Groper)
     */
    public function dig($domain, $recordType = 'A', $username = '')
    {
        $validatedDomain = $this->security->validateShellInput($domain, 'domain');
        if (!$validatedDomain) {
            return "❌ Dominio non valido: $domain";
        }

        $allowedTypes = ['A', 'AAAA', 'MX', 'NS', 'TXT', 'CNAME', 'SOA', 'PTR'];
        $recordType = strtoupper($recordType);
        if (!in_array($recordType, $allowedTypes)) {
            return "❌ Tipo record non supportato. Supportati: " . implode(', ', $allowedTypes);
        }

        if (!$this->isCommandAvailable('dig')) {
            return "❌ Comando DIG non disponibile sul sistema";
        }

        try {
            $startTime = microtime(true);
            $output = $this->executeShellCommand("dig " . escapeshellarg($validatedDomain) . " $recordType +short");
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->logTelegramCommand(0, $username, "dig $domain $recordType", true, $executionTime);

            if (empty(trim($output))) {
                return "ℹ️ Nessun record $recordType trovato per $validatedDomain";
            }

            return "🔍 DIG $recordType per $validatedDomain:\n\n```\n$output\n```";

        } catch (Exception $e) {
            $this->logger->error("DIG command failed: " . $e->getMessage());
            return "❌ Errore durante dig: " . $e->getMessage();
        }
    }

    /**
     * WHOIS
     */
    public function whois($target, $username = '')
    {
        // Valida se è IP o dominio
        if (filter_var($target, FILTER_VALIDATE_IP)) {
            $validatedTarget = $this->security->validateShellInput($target, 'host');
        } else {
            $validatedTarget = $this->security->validateShellInput($target, 'domain');
        }

        if (!$validatedTarget) {
            return "❌ Target non valido: $target";
        }

        if (!$this->isCommandAvailable('whois')) {
            return "❌ Comando WHOIS non disponibile sul sistema";
        }

        try {
            $startTime = microtime(true);
            $output = $this->executeShellCommand("whois " . escapeshellarg($validatedTarget));
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->logTelegramCommand(0, $username, "whois $target", true, $executionTime);

            // Limita output per Telegram
            $lines = explode("\n", $output);
            $filteredLines = [];
            $importantFields = ['domain:', 'registrar:', 'creation date:', 'expiry date:', 'name server:', 'status:', 'country:', 'org:', 'netname:', 'route:'];
            
            foreach ($lines as $line) {
                $lowerLine = strtolower(trim($line));
                foreach ($importantFields as $field) {
                    if (strpos($lowerLine, $field) === 0) {
                        $filteredLines[] = $line;
                        break;
                    }
                }
            }

            $result = empty($filteredLines) ? $output : implode("\n", array_slice($filteredLines, 0, 15));
            
            return "🌐 WHOIS per $validatedTarget:\n\n```\n$result\n```";

        } catch (Exception $e) {
            $this->logger->error("WHOIS command failed: " . $e->getMessage());
            return "❌ Errore durante whois: " . $e->getMessage();
        }
    }

    /**
     * Port scan con nmap
     */
    public function portScan($host, $ports = '1-1000', $username = '')
    {
        $validatedHost = $this->security->validateShellInput($host, 'host');
        if (!$validatedHost) {
            return "❌ Host non valido o non autorizzato: $host";
        }

        $validatedPorts = $this->security->validateShellInput($ports, 'port_range');
        if (!$validatedPorts && !is_numeric($ports)) {
            return "❌ Range porte non valido: $ports";
        }

        if (!$this->isCommandAvailable('nmap')) {
            return "❌ Comando NMAP non disponibile sul sistema";
        }

        try {
            $startTime = microtime(true);
            $portParam = is_numeric($ports) ? "-p $ports" : "-p $validatedPorts";
            $output = $this->executeShellCommand("nmap -sS $portParam --open " . escapeshellarg($validatedHost));
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->logTelegramCommand(0, $username, "nmap $host $ports", true, $executionTime);

            // Filtra solo le porte aperte
            $lines = explode("\n", $output);
            $openPorts = [];
            foreach ($lines as $line) {
                if (preg_match('/(\d+)\/\w+\s+open/', $line)) {
                    $openPorts[] = $line;
                }
            }

            if (empty($openPorts)) {
                return "ℹ️ Nessuna porta aperta trovata su $validatedHost (range: $ports)";
            }

            $result = "🛠️ Port scan $validatedHost (porte aperte):\n\n```\n" . implode("\n", $openPorts) . "\n```";
            return $result;

        } catch (Exception $e) {
            $this->logger->error("Port scan failed: " . $e->getMessage());
            return "❌ Errore durante port scan: " . $e->getMessage();
        }
    }

    /**
     * SSL Certificate check
     */
    public function sslCheck($host, $port = 443, $username = '')
    {
        $validatedHost = $this->security->validateShellInput($host, 'host');
        $validatedPort = $this->security->validateShellInput($port, 'port');
        
        if (!$validatedHost || !$validatedPort) {
            return "❌ Host o porta non validi: $host:$port";
        }

        try {
            $startTime = microtime(true);
            
            // Usa openssl per controllare il certificato
            $cmd = "timeout 10 openssl s_client -connect " . escapeshellarg($validatedHost) . ":$validatedPort -servername " . escapeshellarg($validatedHost) . " 2>/dev/null | openssl x509 -noout -text";
            $output = $this->executeShellCommand($cmd);
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            if (empty($output)) {
                return "❌ Impossibile recuperare certificato SSL da $validatedHost:$validatedPort";
            }

            // Estrai informazioni importanti
            $info = [];
            if (preg_match('/Subject: (.+)/', $output, $matches)) {
                $info[] = "Subject: " . trim($matches[1]);
            }
            if (preg_match('/Issuer: (.+)/', $output, $matches)) {
                $info[] = "Issuer: " . trim($matches[1]);
            }
            if (preg_match('/Not Before: (.+)/', $output, $matches)) {
                $info[] = "Valid From: " . trim($matches[1]);
            }
            if (preg_match('/Not After : (.+)/', $output, $matches)) {
                $info[] = "Valid Until: " . trim($matches[1]);
            }

            $this->logger->logTelegramCommand(0, $username, "ssl_check $host:$port", true, $executionTime);

            return "🔒 SSL Certificate $validatedHost:$validatedPort:\n\n```\n" . implode("\n", $info) . "\n```";

        } catch (Exception $e) {
            $this->logger->error("SSL check failed: " . $e->getMessage());
            return "❌ Errore durante SSL check: " . $e->getMessage();
        }
    }

    /**
     * HTTP/HTTPS check
     */
    public function httpCheck($url, $username = '')
    {
        // Valida URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return "❌ URL non valido: $url";
        }

        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? '';
        
        $validatedHost = $this->security->validateShellInput($host, 'host');
        if (!$validatedHost) {
            return "❌ Host non autorizzato: $host";
        }

        try {
            $startTime = microtime(true);
            
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => true,
                CURLOPT_NOBODY => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'LibreBot/2.0 Health Check'
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
            $error = curl_error($ch);
            curl_close($ch);

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->logTelegramCommand(0, $username, "http_check $url", true, $executionTime);

            if (!empty($error)) {
                return "❌ Errore connessione a $url: $error";
            }

            $status = $httpCode >= 200 && $httpCode < 400 ? '✅' : '❌';
            $responseTime = round($totalTime * 1000, 2);

            $text = "🌐 HTTP Check $url:\n\n";
            $text .= "$status Status: $httpCode\n";
            $text .= "⏱️ Response Time: {$responseTime}ms\n";

            return $text;

        } catch (Exception $e) {
            $this->logger->error("HTTP check failed: " . $e->getMessage());
            return "❌ Errore durante HTTP check: " . $e->getMessage();
        }
    }

    /**
     * Network tools avanzati
     */
    public function networkSummary($host, $username = '')
    {
        $validatedHost = $this->security->validateShellInput($host, 'host');
        if (!$validatedHost) {
            return "❌ Host non valido o non autorizzato: $host";
        }

        try {
            $text = "🌐 Network Summary per $validatedHost:\n\n";
            
            // Ping rapido
            $pingResult = shell_exec("ping -c 1 -W 1 " . escapeshellarg($validatedHost) . " 2>/dev/null");
            if (strpos($pingResult, '1 received') !== false) {
                preg_match('/time=(\d+\.?\d*)/', $pingResult, $matches);
                $pingTime = $matches[1] ?? 'N/A';
                $text .= "📡 Ping: ✅ {$pingTime}ms\n";
            } else {
                $text .= "📡 Ping: ❌ Non raggiungibile\n";
            }

            // DNS lookup
            $dnsResult = shell_exec("nslookup " . escapeshellarg($validatedHost) . " 2>/dev/null | grep 'Address:' | tail -1");
            if (!empty($dnsResult)) {
                $ip = trim(str_replace('Address:', '', $dnsResult));
                $text .= "🔍 DNS: ✅ $ip\n";
            } else {
                $text .= "🔍 DNS: ❌ Risoluzione fallita\n";
            }

            // Port check comuni
            $commonPorts = [22 => 'SSH', 80 => 'HTTP', 443 => 'HTTPS', 53 => 'DNS'];
            $openPorts = [];
            
            foreach ($commonPorts as $port => $service) {
                $connection = @fsockopen($validatedHost, $port, $errno, $errstr, 2);
                if ($connection) {
                    $openPorts[] = "$port ($service)";
                    fclose($connection);
                }
            }

            if (!empty($openPorts)) {
                $text .= "🔓 Porte aperte: " . implode(', ', $openPorts) . "\n";
            } else {
                $text .= "🔓 Porte comuni: Tutte chiuse\n";
            }

            $this->logger->logTelegramCommand(0, $username, "network_summary $host", true, 0);
            return $text;

        } catch (Exception $e) {
            $this->logger->error("Network summary failed: " . $e->getMessage());
            return "❌ Errore durante network summary: " . $e->getMessage();
        }
    }

    // ==================== UTILITY METHODS ====================

    /**
     * Esegue comando shell con timeout e validazione
     */
    private function executeShellCommand($command)
    {
        $timeout = $this->config['security']['command_timeout'] ?? 30;
        $maxLength = $this->config['security']['max_output_length'] ?? 4000;
        
        $descriptorspec = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"]
        ];
        
        $process = proc_open("timeout $timeout $command", $descriptorspec, $pipes);
        
        if (is_resource($process)) {
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            
            $returnCode = proc_close($process);
            
            if (!empty($error) && $returnCode !== 0) {
                throw new Exception("Command failed: $error");
            }
            
            // Limita lunghezza output
            if (strlen($output) > $maxLength) {
                $output = substr($output, 0, $maxLength) . "\n... (output troncato)";
            }
            
            return $output;
        }
        
        throw new Exception("Failed to execute command");
    }

    /**
     * Verifica se un comando è disponibile
     */
    private function isCommandAvailable($command)
    {
        $allowedCommands = $this->config['security']['allowed_shell_commands'] ?? [];
        return in_array($command, $allowedCommands) && shell_exec("which $command");
    }
}