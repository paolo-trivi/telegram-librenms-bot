<?php

namespace LibreBot\Lib;

use LibreBot\Commands\AlertCommands;
use LibreBot\Commands\DeviceCommands;
use LibreBot\Commands\NetworkCommands;
use LibreBot\Commands\SystemCommands;
use Symfony\Contracts\Translation\TranslatorInterface;

class CommandDispatcher
{
    private AlertCommands $alertCommands;
    private DeviceCommands $deviceCommands;
    private NetworkCommands $networkCommands;
    private SystemCommands $systemCommands;
    private TranslatorInterface $translator;

    public function __construct(
        AlertCommands $alertCommands,
        DeviceCommands $deviceCommands,
        NetworkCommands $networkCommands,
        SystemCommands $systemCommands,
        TranslatorInterface $translator
    ) {
        $this->alertCommands = $alertCommands;
        $this->deviceCommands = $deviceCommands;
        $this->networkCommands = $networkCommands;
        $this->systemCommands = $systemCommands;
        $this->translator = $translator;
    }

    public function mapCommandToPermission(string $command): string
    {
        $mapping = [
            'list' => 'alert_list',
            'ack' => 'alert_ack',
            'alert_stats' => 'alert_stats',
            'alert_history' => 'alert_history',
            'bulk_ack' => 'alert_bulk_ack',
            'escalate' => 'alert_escalate',
            'top_alerts' => 'alert_list',
            
            'list_device' => 'device_list',
            'device_status' => 'device_status',
            'port_status' => 'device_status',
            'bandwidth_top' => 'device_list',
            'device_add' => 'device_add',
            'device_remove' => 'device_remove',
            'device_redetect' => 'device_redetect',
            'maintenance' => 'device_maintenance',
            'performance_report' => 'device_status',
            'dashboard' => 'device_dashboard',
            
            'ping' => 'network_ping',
            'trace' => 'network_trace',
            'mtr' => 'network_mtr',
            'ns' => 'network_ns',
            'dig' => 'network_dig',
            'whois' => 'network_whois',
            'port_scan' => 'network_port_scan',
            'ssl_check' => 'network_ssl',
            'http_check' => 'network_http',
            'network_summary' => 'network_ping',
            'nmap' => 'network_port_scan',
            
            'help' => 'system_help',
            'bot_status' => 'bot_status',
            'bot_stats' => 'bot_stats',
            'health' => 'bot_health',
            'log' => 'system_log',
            'system_dashboard' => 'system_dashboard',
            
            'calc' => 'system_calc',
            'convert' => 'system_convert',
            'time' => 'system_time'
        ];
        
        return $mapping[$command] ?? $command;
    }

    public function dispatch(string $command, array $args, int $chatId, ?int $threadId, string $username, string $userRole): string
    {
        switch ($command) {
            // ====== ALERT COMMANDS ======
            case 'list':
                return $this->alertCommands->listAlerts($chatId, $threadId);

            case 'ack':
                if (empty($args[0])) return $this->translator->trans('commands.usage_ack');
                $alertId = $args[0];
                $note = implode(' ', array_slice($args, 1)) ?: 'Acknowledged via Telegram';
                return $this->alertCommands->acknowledgeAlert((int)$alertId, $note, $chatId, $username);

            case 'alert_stats':
                $period = $args[0] ?? 'today';
                return $this->alertCommands->getAlertStats($period);

            case 'alert_history':
                if (empty($args[0])) return $this->translator->trans('commands.usage_alert_history');
                return $this->alertCommands->getAlertHistory($args[0], (int)($args[1] ?? 10));

            case 'bulk_ack':
                if (empty($args[0])) return $this->translator->trans('commands.usage_bulk_ack');
                $pattern = $args[0];
                $note = implode(' ', array_slice($args, 1)) ?: 'Bulk acknowledged';
                return $this->alertCommands->bulkAcknowledge($pattern, $note, $username);

            case 'top_alerts':
                return $this->alertCommands->getTopAlerts((int)($args[0] ?? 10));

            case 'escalate':
                if (count($args) < 2) return $this->translator->trans('commands.usage_escalate');
                return $this->alertCommands->escalateAlert((int)$args[0], implode(' ', array_slice($args, 1)), $username);

            // ====== DEVICE COMMANDS ======
            case 'list_device':
                return $this->deviceCommands->listDevices($args[0] ?? '');

            case 'device_status':
                if (empty($args[0])) return $this->translator->trans('commands.usage_device_status');
                return $this->deviceCommands->getDeviceStatus((int)$args[0]);

            case 'port_status':
                if (count($args) < 2) return $this->translator->trans('commands.usage_port_status');
                return $this->deviceCommands->getPortStatus((int)$args[0], $args[1]);

            case 'bandwidth_top':
                return $this->deviceCommands->getTopBandwidth((int)($args[0] ?? 10));

            case 'device_add':
                if (empty($args[0])) return $this->translator->trans('commands.usage_device_add');
                return $this->deviceCommands->addDevice($args[0], $args[1] ?? 'public', $username);

            case 'device_remove':
                if (empty($args[0])) return $this->translator->trans('commands.usage_device_remove');
                return $this->deviceCommands->removeDevice((int)$args[0], $username);

            case 'device_redetect':
                if (empty($args[0])) return $this->translator->trans('commands.usage_device_redetect');
                return $this->deviceCommands->rediscoverDevice((int)$args[0], $username);

            case 'maintenance':
                if (count($args) < 2) return $this->translator->trans('commands.usage_maintenance');
                $duration = isset($args[2]) ? intval($args[2]) * 3600 : 3600;
                return $this->deviceCommands->setMaintenanceMode((int)$args[0], $args[1], $duration, 'Maintenance via Telegram', $username);

            case 'performance_report':
                if (empty($args[0])) return $this->translator->trans('commands.usage_performance');
                return $this->deviceCommands->getPerformanceReport((int)$args[0], $args[1] ?? '24h');

            case 'dashboard':
                return $this->deviceCommands->getDeviceDashboard();

            // ====== NETWORK COMMANDS ======
            case 'ping':
                if (empty($args[0])) return $this->translator->trans('commands.usage_ping');
                return $this->networkCommands->ping($args[0], $username);

            case 'trace':
                if (empty($args[0])) return $this->translator->trans('commands.usage_trace');
                return $this->networkCommands->traceroute($args[0], $username);

            case 'mtr':
                if (empty($args[0])) return $this->translator->trans('commands.usage_mtr');
                return $this->networkCommands->mtr($args[0], (int)($args[1] ?? 10), $username);

            case 'ns':
                if (empty($args[0])) return $this->translator->trans('commands.usage_ns');
                return $this->networkCommands->nslookup($args[0], $username);

            case 'dig':
                if (empty($args[0])) return $this->translator->trans('commands.usage_dig');
                return $this->networkCommands->dig($args[0], $args[1] ?? 'A', $username);

            case 'whois':
                if (empty($args[0])) return $this->translator->trans('commands.usage_whois');
                return $this->networkCommands->whois($args[0], $username);

            case 'port_scan':
                if (empty($args[0])) return $this->translator->trans('commands.usage_port_scan');
                return $this->networkCommands->portScan($args[0], $args[1] ?? '1-1000', $username);

            case 'ssl_check':
                if (empty($args[0])) return $this->translator->trans('commands.usage_ssl_check');
                return $this->networkCommands->sslCheck($args[0], (int)($args[1] ?? 443), $username);

            case 'http_check':
                if (empty($args[0])) return $this->translator->trans('commands.usage_http_check');
                return $this->networkCommands->httpCheck($args[0], $username);

            case 'network_summary':
                if (empty($args[0])) return $this->translator->trans('commands.usage_network_summary');
                return $this->networkCommands->networkSummary($args[0], $username);

            // ====== SYSTEM COMMANDS ======
            case 'help':
                return $this->systemCommands->getHelp($userRole);

            case 'bot_status':
                return $this->systemCommands->getBotStatus();

            case 'bot_stats':
                return $this->systemCommands->getBotStats($args[0] ?? '24h');

            case 'health':
                return $this->systemCommands->getHealthCheck();

            case 'log':
                return $this->systemCommands->getLog((int)($args[0] ?? 50));

            case 'system_dashboard':
                return $this->systemCommands->getSystemDashboard();

            // ====== UTILITY COMMANDS ======
            case 'calc':
                if (empty($args[0])) return $this->translator->trans('commands.usage_calc');
                return $this->systemCommands->calculateSubnet($args[0]);

            case 'convert':
                if (count($args) < 3) return $this->translator->trans('commands.usage_convert');
                return $this->systemCommands->convertUnits((float)$args[0], $args[1], $args[2]);

            case 'time':
                return $this->systemCommands->getTimeInTimezone($args[0] ?? 'Europe/Rome');

            // ====== LEGACY SUPPORT ======
            case 'nmap':
                if (empty($args[0])) return $this->translator->trans('commands.usage_port_scan');
                return $this->networkCommands->portScan($args[0], '1-1000', $username);

            default:
                return $this->translator->trans('commands.unknown_command', ['command' => $command]);
        }
    }
}
