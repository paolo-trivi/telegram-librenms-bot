# LibreBot v2.0 - Advanced Telegram Bot for LibreNMS

An advanced and modular Telegram bot written in PHP that integrates with [LibreNMS](https://www.librenms.org/) for complete monitoring, alert management, and network troubleshooting — all via Telegram messages!

---

## New in v2.0

### **Advanced Security**
- **Rate limiting** user-configurable
- **Role system** (admin/operator/viewer) with granular permissions
- **Strict input validation** with IP whitelist
- **Structured JSON logging** with automatic rotation
- **Automatic ban** for failed access attempts
- **Complete audit trail** of all operations

### **Modular Architecture**
- **SecurityManager** for authentication and authorization
- **Logger** structured with configurable levels
- **LibreNMSAPI** wrapper with intelligent caching
- **Command classes** separated by category
- **SQLite Database** for cache and tracking

### **New Features**

#### **Advanced Alert Management**
- `/alert_stats` - Detailed alert statistics
- `/alert_history <device_id>` - Alert history for device
- `/bulk_ack <pattern>` - Multiple acknowledge with pattern
- `/escalate <alert_id>` - Alert escalation with notifications
- `/top_alerts` - Most frequent top alerts

#### **Extended Device Management**
- `/device_status <device_id>` - Detailed status with ports and alerts
- `/port_status <device_id> <port>` - Specific port status
- `/device_add <hostname>` - Add devices via bot
- `/device_remove <device_id>` - Securely remove devices
- `/maintenance <device_id> <on/off>` - Maintenance mode
- `/performance_report <device_id>` - Detailed performance report
- `/bandwidth_top` - Top devices by bandwidth usage
- `/dashboard` - General device dashboard

#### **Complete Network Troubleshooting**
- `/mtr <host>` - Continuous My Traceroute
- `/dig <domain> <type>` - Advanced DNS lookup (A, MX, TXT, etc.)
- `/whois <domain/ip>` - Filtered WHOIS information
- `/port_scan <host> <range>` - Port scan using nmap
- `/ssl_check <host>` - Verify SSL certificates
- `/http_check <url>` - HTTP/HTTPS response test
- `/network_summary <host>` - Complete connectivity summary

#### **Bot Management & Monitoring**
- `/bot_status` - Detailed bot status
- `/bot_stats [period]` - Usage and performance statistics
- `/health` - Complete system health check
- `/log [lines]` - Formatted log with filters

#### **Utility Tools**
- `/calc <cidr>` - Subnet/VLAN calculator
- `/convert <value> <from> <to>` - Unit conversions
- `/time <timezone>` - Time in specific timezone

---

## Complete Commands

### Alert Management
```
/list                     → List active alerts
/ack <id> [note]         → Acknowledge alert
/alert_stats             → Alert statistics
/alert_history <dev_id>  → Device alert history
/bulk_ack <pattern>      → Multiple ACK with pattern
/escalate <id> <reason>  → Alert escalation
/top_alerts             → Frequent top alerts
```

### Device Management
```
/list_device [filter]        → List devices
/device_status <id>          → Detailed status
/port_status <id> <port>     → Specific port status
/device_add <hostname>       → Add device
/device_remove <id>          → Remove device
/device_redetect <id>        → Device rediscovery
/maintenance <id> <on/off>   → Maintenance mode
/performance_report <id>     → Performance report
/bandwidth_top              → Top bandwidth usage
/dashboard                  → Device dashboard
```

### Network Tools
```
/ping <host>            → Ping (5 packets)
/trace <host>           → Traceroute
/mtr <host>             → My Traceroute
/ns <host>              → NSLookup
/dig <domain> [type]    → Advanced DNS lookup
/whois <domain/ip>      → WHOIS lookup
/port_scan <host>       → Port scan with nmap
/ssl_check <host>       → SSL verification
/http_check <url>       → HTTP/HTTPS test
/network_summary <host> → Connectivity summary
```

### Bot & System
```
/help                   → Command menu for your role
/bot_status            → Bot status
/bot_stats [period]    → Usage statistics
/health                → System health check
/log [lines]           → Bot log
```

### Utilities
```
/calc <cidr>              → Subnet calculator (e.g. 192.168.1.0/24)
/convert <val> <from> <to> → Unit conversion
/time [timezone]          → Time in timezone
```

---

## Security System

### **Rate Limiting**
- Configurable command limit per minute/hour
- Tracking per single user
- Automatic cleanup of old records

### **Role System**
- **Admin**: Full access to all commands
- **Operator**: Monitoring and troubleshooting commands
- **Viewer**: View-only access to alerts and devices

### **Input Validation**
- IP Whitelist for network commands
- Rigorous shell parameter sanitization
- Configurable timeouts for external commands

### **Logging & Audit**
- JSON structured log with timestamp
- Complete tracking of executed commands
- Automatic log rotation
- Alert on unauthorized access attempts

---

## Quick Installation

### **Prerequisites**
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install php8.0-cli php8.0-curl php8.0-sqlite3 php8.0-json
sudo apt install ping traceroute dnsutils whois mtr-tiny nmap

# CentOS/RHEL
sudo yum install php php-curl php-pdo php-json
sudo yum install iputils traceroute bind-utils whois mtr nmap
```

### **Installation**
```bash
git clone https://github.com/paolo-trivi/telegram-librenms-bot.git
cd telegram-librenms-bot

# Guided installation
php install.php

# Start the bot
php bot.php
```

### **Manual Installation**
```bash
# Copy sample configuration
cp config/config.sample.php config/config.php
nano config/config.php

# Create directories
mkdir -p logs config lib commands

# Start
php bot.php
```

---

## Advanced Configuration

### **config/config.php**
```php
// Security
$security = [
    'rate_limiting' => true,
    'max_commands_per_minute' => 10,
    'ip_whitelist' => ['192.168.0.0/16', '10.0.0.0/8'],
    'max_failed_attempts' => 5,
    'ban_duration' => 3600
];

// User roles
$userPermissions = [
    123456789 => 'admin',      // Chat ID -> Role
    987654321 => 'operator',
    111222333 => 'viewer'
];

// Languages
$language = 'en'; // Supported: en, it, fr, es, de, pt

// Notifications
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

## File Structure

```
telegram-librenms-bot/
├── bot.php                 # Main Bot v2.0
├── install.php             # Installation script
├── config/
│   └── config.php          # Main configuration
├── src/
│   ├── Lib/
│   │   ├── Logger.php          # Logging system
│   │   ├── SecurityManager.php # Security management
│   │   └── LibreNMSAPI.php     # LibreNMS API Wrapper
│   └── Commands/
│       ├── AlertCommands.php   # Alert commands
│       ├── DeviceCommands.php  # Device commands
│       ├── NetworkCommands.php # Network commands
│       └── SystemCommands.php  # System commands
├── lang/
│   ├── en.php              # English language (Default)
│   ├── it.php              # Italian language
│   └── ...                 # Other languages
├── logs/
│   ├── bot.log            # Main log
│   └── bot.db             # SQLite Database
└── docs/
    └── API.md             # API Documentation
```

---

## Performance & Monitoring

### **Self-Monitoring**
The bot automatically monitors:
- Memory and CPU usage
- Command response time
- LibreNMS API errors
- Failed access attempts
- Cache hit/miss ratio

### **Health Check**
```bash
# Via Telegram
/health

# Via CLI
php -r "require 'bot.php'; echo $systemCommands->getHealthCheck();"
```

### **Available Metrics**
- Commands executed per period
- Active users
- Top used commands
- Errors by category
- Performance trends

---

## Extensibility

### **Plugin System** (Roadmap)
```php
// plugins/WeatherPlugin.php
class WeatherPlugin {
    public function execute($args) {
        return "Weather: 20°C, sunny";
    }
}
```

### **Custom Commands**
Add new commands by modifying `executeCommand()` in `bot.php`

### **API Extensions**
Extend `LibreNMSAPI.php` for new LibreNMS functionalities

---

## Roadmap v3.0

### **Planned Features**
- [ ] **Web Dashboard** for browser-based management
- [ ] **Plugin system** for custom commands
- [ ] **Scheduled tasks** and automation
- [ ] **Multi-tenant** support
- [ ] **Webhook** integrations
- [ ] **Automatic Reports** via email
- [ ] **Charts** and visualizations
- [ ] **REST API** for external integrations


---

## Contributing

Contributions are **welcome**!

### **How to Contribute**
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/new-feature`)
3. Commit your changes (`git commit -am 'Add new feature'`)
4. Push to the branch (`git push origin feature/new-feature`)
5. Open a Pull Request

### **Contribution Areas**
- **Bug fixes**
- **New features**
- **Documentation**
- **Automated tests**
- **Translations**
- **UI/UX improvements**

---

## License

This project is distributed under the **MIT** license. See the [LICENSE](LICENSE) file for details.

---

**If LibreBot is useful to you, leave a star on GitHub!**

**Useful Links:**
- [LibreNMS](https://www.librenms.org/)
- [Telegram Bot API](https://core.telegram.org/bots/api)
- [PHP Documentation](https://www.php.net/docs.php)

---

*Last update: December 2025 - v2.0.1*
