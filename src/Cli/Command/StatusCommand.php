<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StatusCommand extends Command
{
    public function configure()
    {
        $this->setName('status');
        $this->setDescription('show status of rooter');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dnsmasqPid = file_get_contents(ROOTER_HOME_DIR . '/dnsmasq/dnsmasq.pid');
        $dnsmasqStatus = $dnsmasqPid ? 'running' : 'stopped';

        $traefikPid = file_get_contents(ROOTER_HOME_DIR . '/traefik/traefik.pid');
        $traefikStatus = $traefikPid ? 'running' : 'stopped';

        $table = new Table($output);
        $table->setStyle('box');
        $table->setHeaders(['name', 'status', 'pid']);
        $table->setRows([
            ['dnsmasq', $dnsmasqStatus, $dnsmasqPid],
            new TableSeparator(),
            ['traefik', $traefikStatus, $traefikPid],

        ]);
        $table->render();

        return 0;
    }
}
