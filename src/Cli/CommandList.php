<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli;

use Symfony\Component\Console\Command\Command;

class CommandList
{
    /** @return Command[] */
    public function getCommands(): array
    {
        return [
            new \RunAsRoot\Rooter\Cli\Command\Env\InitEnvCommand(),
            new \RunAsRoot\Rooter\Cli\Command\Env\RegisterEnvCommand(),
            new \RunAsRoot\Rooter\Cli\Command\Env\ListEnvCommand(),
            new \RunAsRoot\Rooter\Cli\Command\Env\ShowEnvCommand(),
            new \RunAsRoot\Rooter\Cli\Command\Env\StartCommand(),
            new \RunAsRoot\Rooter\Cli\Command\Env\StopCommand(),
            new \RunAsRoot\Rooter\Cli\Command\Env\StatusCommand(),
            new \RunAsRoot\Rooter\Cli\Command\Nginx\InitNginxCommand(),
            new \RunAsRoot\Rooter\Cli\Command\Traefik\InitTraefikConfigCommand(),
            new \RunAsRoot\Rooter\Cli\Command\Traefik\RegisterTraefikConfigCommand(),
            new \RunAsRoot\Rooter\Cli\Command\Traefik\StartTraefikCommand(),
            new \RunAsRoot\Rooter\Cli\Command\Traefik\StopTraefikCommand(),
            new \RunAsRoot\Rooter\Cli\Command\Traefik\ShowTraefikLogCommand(),
            new \RunAsRoot\Rooter\Cli\Command\Traefik\ShowTraefikStatusCommand(),
            new \RunAsRoot\Rooter\Cli\Command\Traefik\OpenTraefikDashboardCommand(),
            new \RunAsRoot\Rooter\Cli\Command\Dnsmasq\StartDnsmasqCommand(),
            new \RunAsRoot\Rooter\Cli\Command\Dnsmasq\StopDnsmasqCommand(),
            new \RunAsRoot\Rooter\Cli\Command\Dnsmasq\ShowDnsmasqStatusCommand(),
            new \RunAsRoot\Rooter\Cli\Command\Dnsmasq\InitDnsmasqConfigCommand(),
            new \RunAsRoot\Rooter\Cli\Command\Magento2\InstallMagento2DbCommand(),
            new \RunAsRoot\Rooter\Cli\Command\Magento2\RefreshMagento2DbCommand(),
            new \RunAsRoot\Rooter\Cli\Command\Mysql\MysqlCliCommand(),
            new \RunAsRoot\Rooter\Cli\Command\Mysql\MysqlDumpCommand(),
            new \RunAsRoot\Rooter\Cli\Command\Mysql\MysqlImportCommand(),
            new \RunAsRoot\Rooter\Cli\Command\AmqpAdminCommand(),
            new \RunAsRoot\Rooter\Cli\Command\MailhogCommand(),
            new \RunAsRoot\Rooter\Cli\Command\InfoCommand(),
            new \RunAsRoot\Rooter\Cli\Command\InitCommand(),
            new \RunAsRoot\Rooter\Cli\Command\InstallCommand(),
            new \RunAsRoot\Rooter\Cli\Command\QueriousCommand(),
            new \RunAsRoot\Rooter\Cli\Command\RedisCliCommand(),
            new \RunAsRoot\Rooter\Cli\Command\StartCommand(),
            new \RunAsRoot\Rooter\Cli\Command\StopCommand(),
            new \RunAsRoot\Rooter\Cli\Command\StatusCommand(),
            new \RunAsRoot\Rooter\Cli\Command\TablePlusCommand(),
        ];
    }
}
