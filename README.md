# 🤖 TelegramBot per LibreNMS

Un bot Telegram scritto in PHP che interagisce con [LibreNMS](https://www.librenms.org/) per il monitoraggio e la gestione degli alert, dei dispositivi e del troubleshooting di rete — tutto via messaggi Telegram!

---

## 🚀 Funzionalità principali

### Comandi supportati

- **`/help`** — Mostra il menu dei comandi disponibili
- **`/list`** — Elenca gli alert attivi presenti su LibreNMS
- **`/ack <id> [nota]`** — Esegue un *acknowledge* su un alert con nota opzionale
- **`/list_device [filtro]`** — Elenca dispositivi filtrabili per hostname, IP, OS, sysName, display, type
- **`/ping <ip|host>`** — Ping (5 pacchetti) verso il target specificato
- **`/trace <ip|host>`** — Traceroute verso il target specificato
- **`/nmap <ip|host>`** — Scansione TCP SYN con rilevamento versione servizi
- **`/ns <ip|host>`** — Richiesta DNS via `nslookup`
- **`/log`** — Mostra gli ultimi log generati dal bot

---

## 🔐 Sicurezza

- Accesso limitato a chat ID autorizzati
- Supporto a thread specifici per gruppi
- Nessun comando disponibile pubblicamente

---

## 🛠 Requisiti

- PHP 8.0 o superiore
- Estensione `curl`
- `ping`, `traceroute`, `nslookup` e `nmap` disponibili nel sistema
- Bot Telegram già creato tramite [BotFather](https://t.me/botfather)
- Token API LibreNMS con privilegi su `/alerts` e `/devices`

---

## ⚙️ Installazione

```bash
git clone https://github.com/paolo-trivi/telegram-librenms-bot.git
cd telegram-librenms-bot
nano config.php # Inserisci token, URL LibreNMS e chat_id autorizzati
php bot.php
```

Consigliato: esegui il bot in background con `screen`, `tmux` o crea un servizio systemd.

---

## 🔧 Configurazione rapida

Modifica le variabili all’interno di config.php:

```php
$botToken = 'TOKEN_TELEGRAM';
$librenmsUrl = 'http://librenms.example.net';  
$librenmsToken = 'TOKEN_API_LIBRENMS';
$allowedChatIds = [12345678]; // Chat o gruppo autorizzati
$allowedThreads = [null]; // Facoltativo: thread ID per gruppi
```

---

## 📄 Licenza

Questo progetto è distribuito sotto licenza **MIT**. Sei libero di usarlo, modificarlo e redistribuirlo.

---

## 🤝 Contributi

Pull request, fix, nuove feature e suggerimenti sono **più che benvenuti**!

Puoi contribuire aprendo una PR su:
👉 [https://github.com/paolo-trivi/telegram-librenms-bot](https://github.com/paolo-trivi/telegram-librenms-bot)

---
