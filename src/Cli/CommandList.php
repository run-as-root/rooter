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
            new \RunAsRoot\Rooter\Cli\Command\Traefik\InitTraefikConfigCommand(),
            new \RunAsRoot\Rooter\Cli\Command\Traefik\RegisterTraefikConfigCommand(),
            new \RunAsRoot\Rooter\Cli\Command\Mysql\MysqlCliCommand(),
            new \RunAsRoot\Rooter\Cli\Command\Mysql\MysqlDumpCommand(),
            new \RunAsRoot\Rooter\Cli\Command\MailhogCommand(),
            new \RunAsRoot\Rooter\Cli\Command\RedisCliCommand(),
            new \RunAsRoot\Rooter\Cli\Command\TablePlusCommand(),
            new \RunAsRoot\Rooter\Cli\Command\QueriousCommand(),
        ];
    }
}
