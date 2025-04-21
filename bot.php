<?php
// ===========================================================
//  🤖 LibreBot v.0.2
// ===========================================================
//  Autore: Paolo Trivisonno - Ultimo aggiornamento: 05 apr 2025
//  Integrazione tra LibreNMS e Telegram per gestire:
//  monitoraggio, troubleshooting e controllo dei dispositivi
//  di rete in tempo reale.
//
//  🔐 Sicurezza:
//  - Accesso consentito solo a chat ID autorizzati e thread specifici
//
//  📌 Funzioni
//  -----------------------------------------------------------
//  📖 /help
//     → Mostra il menu dei comandi disponibili
//  🔔 /list
//     → Elenca gli alert attivi da LibreNMS
//  ✅ /ack <alert_id> [nota]
//     → Esegue l’ACK di un alert su LibreNMS con nota opzionale
//  💻 /list_device [filtro]
//     → Mostra l’elenco dei dispositivi attivi, filtrabili per:
//       hostname, IP, OS, sysName, display o tipo
//  📡 /ping <host|ip>
//     → Esegue un ping di 5 pacchetti verso il target indicato
//  🌍 /trace <host|ip>
//     → Esegue un traceroute verso il target indicato
//  🔎 /ns <host|ip>
//     → Effettua una richiesta DNS via nslookup
//  🛠️ /nmap <host|ip>
//     → Esegue una scansione TCP SYN con rilevamento versione servizi
//  📜 /log
//     → Mostra gli ultimi eventi loggati dal bot (file: bot.log)
//
//  📤 Formattazione output: Telegram MarkdownV2 con escape automatico
//  🧠 Backend: REST API di LibreNMS + shell command
//  ⚠️ ATTENZIONE: Non eseguire questo bot come root!
// ===========================================================
require_once __DIR__ . '/config.php';
$telegramApi = "https://api.telegram.org/bot$botToken";
$lastUpdateId = 0;

echo "🤖 Bot avviato. Autorizzati: " . implode(', ', $allowedChatIds) . " | Thread: [nessuno, 24]\n";

/**
 * logEvent($line)
 * Scrive un evento nel file di log.
 * 
 * @param string $line La stringa da scrivere nel log.
 */
function logEvent($line)
{
    global $logFile;
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $line . "\n", FILE_APPEND);
}

/**
 * sendTelegram($chatId, $text, $threadId = null)
 * Invia un messaggio a un utente o gruppo su Telegram.
 * 
 * @param int $chatId ID della chat Telegram.
 * @param string $text Testo del messaggio da inviare.
 * @param int|null $threadId ID del thread (opzionale).
 */
function sendTelegram($chatId, $text, $threadId = null)
{
    global $telegramApi;
    $params = [
        'chat_id' => $chatId,
        'text' => $text
    ];
    if ($threadId !== null) {
        $params['message_thread_id'] = $threadId;
    }
    $url = $telegramApi . '/sendMessage?' . http_build_query($params);
    echo "📤 Invio messaggio a Telegram: $url\n";
    $res = @file_get_contents($url);
    if ($res === false) {
        echo "❌ Errore nell'invio Telegram\n";
        logEvent("[ERRORE] Telegram non ha risposto\nURL: $url\n");
    } else {
        echo "✅ Messaggio inviato\n";
    }
}

// Ciclo infinito per gestire gli aggiornamenti ricevuti dal bot.
while (true) {
    $url = "$telegramApi/getUpdates?timeout=10&offset=" . ($lastUpdateId + 1);
    $response = file_get_contents($url);
    $updates = json_decode($response, true);

    if (!$updates || !isset($updates['result'])) {
        sleep(5);
        continue;
    }

    foreach ($updates['result'] as $update) {
        $lastUpdateId = $update['update_id'];
        $message = isset($update['message']['text']) ? $update['message']['text'] : '';
        $message = preg_replace('/@[\w_]+$/', '', $message); // Rimuove eventuali menzioni al bot
        $chatId = isset($update['message']['chat']['id']) ? $update['message']['chat']['id'] : null;
        $threadId = isset($update['message']['message_thread_id']) ? $update['message']['message_thread_id'] : null;
        $from = isset($update['message']['from']['username']) ? $update['message']['from']['username'] : 'utente';

        echo "\n➡️ [$from | chat_id: $chatId | thread: " . ($threadId !== null ? $threadId : 'nessuno') . "] $message\n";
        logEvent("[{$from}] $message");

        // Controlla se l'utente è autorizzato
        if (!in_array($chatId, $allowedChatIds) || ($chatId < 0 && !in_array($threadId, $allowedThreads))) {
            echo "🚫 Accesso negato.\n";
            sendTelegram($chatId, "❌ Accesso negato.", $threadId);
            continue;
        }

        // Gestione dei comandi
        if (preg_match('/^\/log$/i', $message)) {
            // Mostra gli ultimi log
            $logContent = file_exists($logFile) ? file_get_contents($logFile) : "Nessun log disponibile.";
            $reply = substr($logContent, -3800);
            sendTelegram($chatId, "🗒 Ultimi log:\n\n$reply", $threadId);
        } elseif (preg_match('/^\/help$/i', $message)) {
            // Mostra il menu dei comandi disponibili
            $text = "📖 Comandi disponibili:\n";
            $text .= "/ack <id> [nota] → Acknowledge alert LibreNMS\n";
            $text .= "/list → Elenca alert attivi\n";
            $text .= "/list_device [filtro] → Mostra dispositivi\n";
            $text .= "/ping <ip> → Ping\n";
            $text .= "/trace <ip> → Traceroute\n";
            $text .= "/nmap <ip> → Nmap scan\n";
            $text .= "/ns <host> → NSLookup\n";
            $text .= "/log → Mostra log\n";
            $text .= "/help → Mostra questo menu";
            sendTelegram($chatId, $text, $threadId);
        } elseif (preg_match('/^\/ack\s+(\d+)(?:\s+(.+))?/i', $message, $matches)) {
            // Esegue l'acknowledge di un alert su LibreNMS
            $alertId = $matches[1];
            $note = isset($matches[2]) ? $matches[2] : 'Acknowledged via Telegram';
            $data = json_encode(['note' => $note, 'until_clear' => false]);
            $ch = curl_init("$librenmsUrl/api/v0/alerts/$alertId");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "X-Auth-Token: $librenmsToken",
                "Content-Type: application/json"
            ]);
            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $msg = ($httpCode === 200)
                ? "✅ ACK eseguito su alert $alertId\nNota: $note"
                : "❌ Errore ACK alert $alertId\nCodice: $httpCode\n$res";
            sendTelegram($chatId, $msg, $threadId);
        } elseif (preg_match('/^\/list$/i', $message)) {
            // Elenca gli alert attivi su LibreNMS con dettagli recuperati da device_id e rule_id
            $ch = curl_init("$librenmsUrl/api/v0/alerts?state=1");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "X-Auth-Token: $librenmsToken",
                "Content-Type: application/json"
            ]);
            $res = curl_exec($ch);
            curl_close($ch);
            $alerts = json_decode($res, true);
            $alerts = isset($alerts['alerts']) ? $alerts['alerts'] : [];
            $text = empty($alerts) ? "✅ Nessun alert attivo." : "⚠️ Alert attivi:\n";

            foreach ($alerts as $a) {
                $id = $a['id'];
                $timestamp = $a['timestamp'] ?? 'n/d';
                $device_id = $a['device_id'] ?? null;
                $rule_id = $a['rule_id'] ?? null;

                // Nome regola
                $ruleName = 'N/D';
                if ($rule_id !== null) {
                    $chRule = curl_init("$librenmsUrl/api/v0/rules/$rule_id");
                    curl_setopt($chRule, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($chRule, CURLOPT_HTTPHEADER, [
                        "X-Auth-Token: $librenmsToken",
                        "Content-Type: application/json"
                    ]);
                    $resRule = curl_exec($chRule);
                    curl_close($chRule);
                    $ruleData = json_decode($resRule, true);
                    if (isset($ruleData['rules'][0]['name'])) {
                        $ruleName = $ruleData['rules'][0]['name'];
                    }
                }

                // Info device
                $hostname = 'sconosciuto';
                $ip = 'n/a';
                if ($device_id !== null) {
                    $chDev = curl_init("$librenmsUrl/api/v0/devices/$device_id");
                    curl_setopt($chDev, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($chDev, CURLOPT_HTTPHEADER, [
                        "X-Auth-Token: $librenmsToken",
                        "Content-Type: application/json"
                    ]);
                    $resDev = curl_exec($chDev);
                    curl_close($chDev);
                    $devData = json_decode($resDev, true);
                    if (isset($devData['devices'][0])) {
                        $hostname = $devData['devices'][0]['hostname'] ?? $hostname;
                        $ip = $devData['devices'][0]['ip'] ?? $ip;
                    }
                }

                $text .= "🆔 $id | 📅 $timestamp\n";
                $text .= "💥 Tipo: $ruleName\n";
                $text .= "🖥️ Host: $hostname\n";
                $text .= "🌐 IP: $ip\n";
                $text .= "-------------------------\n";
            }

            sendTelegram($chatId, $text, $threadId);
        } elseif (preg_match('/^\/list_device(?:\s+(.+))?/i', $message, $matches)) {
            // Mostra i dispositivi attivi su LibreNMS, con filtro opzionale
            $filtro = isset($matches[1]) ? trim($matches[1]) : '';
            $ch = curl_init("$librenmsUrl/api/v0/devices?type=active");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Auth-Token: $librenmsToken"]);
            $res = curl_exec($ch);
            curl_close($ch);
            $devices = json_decode($res, true)['devices'] ?? [];
            if ($filtro !== '') {
                $devices = array_filter($devices, function ($d) use ($filtro) {
                    foreach (['hostname', 'sysName', 'os', 'display', 'type', 'ip'] as $campo) {
                        if (isset($d[$campo]) && stripos($d[$campo], $filtro) !== false)
                            return true;
                    }
                    return false;
                });
            }
            if (empty($devices)) {
                sendTelegram($chatId, "❌ Nessun dispositivo trovato.", $threadId);
            } else {
                $msg = "📟 Dispositivi trovati:\n";
                foreach ($devices as $d) {
                    $msg .= "🖥️ {$d['hostname']}";
                    $msg .= isset($d['sysName']) ? " ({$d['sysName']})" : '';
                    $msg .= "\n📶 Stato: " . (isset($d['status']) ? $d['status'] : 'unknown') . " | ID: {$d['device_id']}\n";
                    if (!empty($d['ip']))
                        $msg .= "🌐 IP: {$d['ip']}\n";
                    if (!empty($d['os']))
                        $msg .= "🧠 OS: {$d['os']}\n";
                    $msg .= "\n";
                }
                sendTelegram($chatId, $msg, $threadId);
            }
        } elseif (preg_match('/^\/ping\s+([\w\.\-]+)$/i', $message, $matches)) {
            // Esegue un ping verso un host specificato
            $host = escapeshellarg($matches[1]);
            $out = shell_exec("ping -c 5 -W 2 $host 2>&1");
            sendTelegram($chatId, "📡 Ping:
$out", $threadId);
        } elseif (preg_match('/^\/trace\s+([\w\.\-]+)$/i', $message, $matches)) {
            // Esegue un traceroute verso un host specificato
            $host = escapeshellarg($matches[1]);
            $out = shell_exec("traceroute $host 2>&1");
            sendTelegram($chatId, "🌍 Traceroute:
$out", $threadId);
        } elseif (preg_match('/^\/nmap\s+([\w\.\-]+)$/i', $message, $matches)) {
            // Esegue una scansione Nmap verso un host specificato
            $host = escapeshellarg($matches[1]);
            $out = shell_exec("nmap -sS -sV -v -v -Pn $host 2>&1");
            $out = implode("\n", array_slice(explode("\n", $out), 0, 30));
            sendTelegram($chatId, "🛠️ Nmap:
$out", $threadId);
        } elseif (preg_match('/^\/ns\s+([\w\.\-]+)$/i', $message, $matches)) {
            // Esegue un NSLookup verso un host specificato
            $host = escapeshellarg($matches[1]);
            $out = shell_exec("nslookup $host 2>&1");
            sendTelegram($chatId, "🔎 NSLookup:
$out", $threadId);
        }
    }
    sleep(2);
}
