# 🤖 LibreBot v2.0 - Advanced Telegram Bot for LibreNMS

Un bot Telegram avanzato e modulare scritto in PHP che si integra con [LibreNMS](https://www.librenms.org/) per il monitoraggio completo, gestione alert e troubleshooting di rete — tutto tramite messaggi Telegram!

---

## 🚀 Novità v2.0

### 🔐 **Sicurezza Avanzata**
- **Rate limiting** configurabile per utente
- **Sistema di ruoli** (admin/operator/viewer) con permessi granulari
- **Validazione input rigorosa** con whitelist IP
- **Logging strutturato** JSON con rotazione automatica
- **Ban automatico** per tentativi di accesso falliti
- **Audit trail** completo di tutte le operazioni

### 🏗️ **Architettura Modulare**
- **SecurityManager** per autenticazione e autorizzazione
- **Logger** strutturato con livelli configurabili
- **LibreNMS API** wrapper con caching intelligente
- **Command classes** separate per categoria
- **Database SQLite** per cache e tracking

### 🆕 **Nuove Funzionalità**

#### 📊 **Alert Management Avanzato**
- `/alert_stats` - Statistiche alert dettagliate
- `/alert_history <device_id>` - Storico alert per dispositivo
- `/bulk_ack <pattern>` - Acknowledge multiplo con pattern
- `/escalate <alert_id>` - Escalation alert con notifiche
- `/top_alerts` - Top alert più frequenti

#### 🖥️ **Device Management Esteso**
- `/device_status <device_id>` - Status dettagliato con porte e alert
- `/port_status <device_id> <port>` - Status porta specifica
- `/device_add <hostname>` - Aggiunta dispositivi via bot
- `/device_remove <device_id>` - Rimozione sicura dispositivi
- `/maintenance <device_id> <on/off>` - Modalità manutenzione
- `/performance_report <device_id>` - Report performance dettagliato
- `/bandwidth_top` - Top dispositivi per utilizzo banda
- `/dashboard` - Dashboard generale dispositivi

#### 🌐 **Network Troubleshooting Completo**
- `/mtr <host>` - My Traceroute continuo
- `/dig <domain> <type>` - DNS lookup avanzato (A, MX, TXT, ecc.)
- `/whois <domain/ip>` - Informazioni WHOIS filtrate
- `/port_scan <host> <range>` - Scan porte con nmap
- `/ssl_check <host>` - Verifica certificati SSL
- `/http_check <url>` - Test risposta HTTP/HTTPS
- `/network_summary <host>` - Riepilogo completo connettività

#### 🤖 **Bot Management & Monitoring**
- `/bot_status` - Status dettagliato del bot
- `/bot_stats [period]` - Statistiche utilizzo e performance
- `/health` - Health check completo del sistema
- `/log [lines]` - Log formattato con filtri

#### 🧮 **Utility Tools**
- `/calc <cidr>` - Calcolatrice subnet/VLAN
- `/convert <value> <from> <to>` - Conversioni unità
- `/time <timezone>` - Orario in timezone specifico

---

## 📋 Comandi Completi

### 🔔 Alert Management
```
/list                     → Elenca alert attivi
/ack <id> [nota]         → Acknowledge alert
/alert_stats             → Statistiche alert
/alert_history <dev_id>  → Storico alert dispositivo
/bulk_ack <pattern>      → ACK multiplo con pattern
/escalate <id> <motivo>  → Escalation alert
/top_alerts             → Top alert frequenti
```

### 🖥️ Device Management
```
/list_device [filtro]        → Lista dispositivi
/device_status <id>          → Status dettagliato
/port_status <id> <porta>    → Status porta specifica
/device_add <hostname>       → Aggiungi dispositivo
/device_remove <id>          → Rimuovi dispositivo
/device_redetect <id>        → Ri-discovery dispositivo
/maintenance <id> <on/off>   → Modalità manutenzione
/performance_report <id>     → Report performance
/bandwidth_top              → Top utilizzo banda
/dashboard                  → Dashboard dispositivi
```

### 🌐 Network Tools
```
/ping <host>            → Ping (5 pacchetti)
/trace <host>           → Traceroute
/mtr <host>             → My Traceroute
/ns <host>              → NSLookup
/dig <domain> [type]    → DNS lookup avanzato
/whois <domain/ip>      → WHOIS lookup
/port_scan <host>       → Scan porte con nmap
/ssl_check <host>       → Verifica SSL
/http_check <url>       → Test HTTP/HTTPS
/network_summary <host> → Riepilogo connettività
```

### 🤖 Bot & System
```
/help                   → Menu comandi per il tuo ruolo
/bot_status            → Status del bot
/bot_stats [period]    → Statistiche utilizzo
/health                → Health check sistema
/log [lines]           → Log del bot
```

### 🧮 Utilities
```
/calc <cidr>              → Calcola subnet (es. 192.168.1.0/24)
/convert <val> <da> <a>   → Converti unità
/time [timezone]          → Orario in timezone
```

---

## 🔐 Sistema di Sicurezza

### **Rate Limiting**
- Limite configurabile comandi per minuto/ora
- Tracking per singolo utente
- Cleanup automatico vecchi record

### **Sistema Ruoli**
- **Admin**: Accesso completo a tutti i comandi
- **Operator**: Comandi di monitoraggio e troubleshooting
- **Viewer**: Solo visualizzazione alert e dispositivi

### **Validazione Input**
- Whitelist IP per comandi di rete
- Sanitizzazione rigorosa parametri shell
- Timeout configurabili per comandi esterni

### **Logging & Audit**
- Log strutturato JSON con timestamp
- Tracking completo comandi eseguiti
- Rotazione automatica log
- Alert su tentativi di accesso non autorizzati

---

## ⚙️ Installazione Rapida

### **Prerequisiti**
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install php8.0-cli php8.0-curl php8.0-sqlite3 php8.0-json
sudo apt install ping traceroute dnsutils whois mtr-tiny nmap

# CentOS/RHEL
sudo yum install php php-curl php-pdo php-json
sudo yum install iputils traceroute bind-utils whois mtr nmap
```

### **Installazione**
```bash
git clone https://github.com/paolo-trivi/telegram-librenms-bot.git
cd telegram-librenms-bot

# Installazione guidata
php install.php

# Avvia il bot
php bot_v2.php
```

### **Installazione Manuale**
```bash
# Copia configurazione
cp config.sample.php config/config.php
nano config/config.php

# Crea directory
mkdir -p logs config lib commands

# Avvia
php bot_v2.php
```

---

## 🔧 Configurazione Avanzata

### **config/config.php**
```php
// Sicurezza
$security = [
    'rate_limiting' => true,
    'max_commands_per_minute' => 10,
    'ip_whitelist' => ['192.168.0.0/16', '10.0.0.0/8'],
    'max_failed_attempts' => 5,
    'ban_duration' => 3600
];

// Ruoli utente
$userPermissions = [
    123456789 => 'admin',      // Chat ID -> Ruolo
    987654321 => 'operator',
    111222333 => 'viewer'
];

// Notifiche
$notifications = [
    'daily_report' => true,
    'daily_report_time' => '08:00',
    'emergency_contacts' => ['+39123456789']
];
```

### **Logging**
```php
$debug = [
    'enabled' => true,
    'log_level' => 'DEBUG',    // DEBUG, INFO, WARNING, ERROR
    'verbose_logging' => true
];
```

---

## 🗂️ Struttura File

```
telegram-librenms-bot/
├── bot_v2.php              # Bot principale v2.0
├── bot.php                 # Bot legacy v1.0
├── install.php             # Script installazione
├── config/
│   └── config.php          # Configurazione principale
├── lib/
│   ├── Logger.php          # Sistema logging
│   ├── SecurityManager.php # Gestione sicurezza
│   └── LibreNMSAPI.php     # Wrapper API LibreNMS
├── commands/
│   ├── AlertCommands.php   # Comandi alert
│   ├── DeviceCommands.php  # Comandi dispositivi
│   ├── NetworkCommands.php # Comandi rete
│   └── SystemCommands.php  # Comandi sistema
├── logs/
│   ├── bot.log            # Log principale
│   └── bot.db             # Database SQLite
└── docs/
    └── API.md             # Documentazione API
```

---

## 📊 Performance & Monitoring

### **Self-Monitoring**
Il bot monitora automaticamente:
- Utilizzo memoria e CPU
- Tempo di risposta comandi
- Errori API LibreNMS
- Tentativi di accesso falliti
- Cache hit/miss ratio

### **Health Check**
```bash
# Via Telegram
/health

# Via CLI
php -r "require 'bot_v2.php'; echo $systemCommands->getHealthCheck();"
```

### **Metriche Disponibili**
- Comandi eseguiti per periodo
- Utenti attivi
- Top comandi utilizzati
- Errori per categoria
- Performance trends

---

## 🔌 Estensibilità

### **Plugin System** (Roadmap)
```php
// plugins/WeatherPlugin.php
class WeatherPlugin {
    public function execute($args) {
        return "🌤️ Meteo: 20°C, sereno";
    }
}
```

### **Custom Commands**
Aggiungi nuovi comandi modificando `executeCommand()` in `bot_v2.php`

### **API Extensions**
Estendi `LibreNMSAPI.php` per nuove funzionalità LibreNMS

---

## 🚀 Roadmap v3.0

### **Planned Features**
- [ ] **Web Dashboard** per gestione via browser
- [ ] **Plugin system** per comandi personalizzati
- [ ] **Scheduled tasks** e automazione
- [ ] **Multi-tenant** support
- [ ] **Webhook** integrations
- [ ] **Report** automatici via email
- [ ] **Grafici** e visualizzazioni
- [ ] **API REST** per integrazioni esterne

### **Integration Roadmap**
- [ ] **Zabbix** support
- [ ] **PRTG** integration
- [ ] **Nagios** compatibility
- [ ] **Slack/Teams** bridges
- [ ] **ITSM** ticket creation

---

## 🤝 Contributi

I contributi sono **benvenuti**! 

### **Come Contribuire**
1. Fork del repository
2. Crea branch per la feature (`git checkout -b feature/nuova-funzionalita`)
3. Commit delle modifiche (`git commit -am 'Aggiunge nuova funzionalità'`)
4. Push del branch (`git push origin feature/nuova-funzionalita`)
5. Apri una Pull Request

### **Aree di Contributo**
- 🐛 **Bug fixes**
- ✨ **Nuove funzionalità**
- 📖 **Documentazione**
- 🧪 **Test automatici**
- 🌍 **Traduzioni**
- 🎨 **UI/UX improvements**

---

## 📄 Licenza

Questo progetto è distribuito sotto licenza **MIT**. Vedi il file [LICENSE](LICENSE) per i dettagli.

---

## 🆘 Supporto

### **Documentazione**
- 📖 [Wiki completa](https://github.com/paolo-trivi/telegram-librenms-bot/wiki)
- 🎥 [Video tutorials](https://github.com/paolo-trivi/telegram-librenms-bot/wiki/Videos)
- 💡 [FAQ](https://github.com/paolo-trivi/telegram-librenms-bot/wiki/FAQ)

### **Community**
- 🐛 [Issues](https://github.com/paolo-trivi/telegram-librenms-bot/issues) per bug e feature request
- 💬 [Discussions](https://github.com/paolo-trivi/telegram-librenms-bot/discussions) per domande e supporto
- 📱 [Telegram Group](https://t.me/librebotcommunity) per supporto in tempo reale

### **Professional Support**
Per supporto professionale e consulenza:
- 📧 Email: paolo.trivisonno@example.com
- 💼 LinkedIn: [Paolo Trivisonno](https://linkedin.com/in/paolo-trivisonno)

---

## 🙏 Ringraziamenti

- **LibreNMS Team** per l'eccellente piattaforma di monitoring
- **Telegram Bot API** per le potenti API
- **Community** per feedback e contributi
- **Beta testers** per i test e suggerimenti

---

**⭐ Se LibreBot ti è utile, lascia una stella su GitHub!**

**🔗 Links Utili:**
- [LibreNMS](https://www.librenms.org/)
- [Telegram Bot API](https://core.telegram.org/bots/api)
- [PHP Documentation](https://www.php.net/docs.php)

---

*Ultimo aggiornamento: Dicembre 2024 - v2.0*