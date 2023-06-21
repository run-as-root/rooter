<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command;

use RunAsRoot\Rooter\Cli\Command\Env\ListEnvCommand;
use RunAsRoot\Rooter\Config\DnsmasqConfig;
use RunAsRoot\Rooter\Config\TraefikConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StatusCommand extends Command
{
    private DnsmasqConfig $dnsmasqConfig;
    private TraefikConfig $traefikConfig;

    public function configure()
    {
        $this->setName('status');
        $this->setDescription('show status of rooter');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->traefikConfig = new TraefikConfig();
        $this->dnsmasqConfig = new DnsmasqConfig();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dnsmasqPid = $this->getPidFromFile($this->dnsmasqConfig->getPidFile());
        $dnsmasqStatus = $this->isProcessRunning($dnsmasqPid) ? 'running' : 'stopped';

        $traefikPid = $this->getPidFromFile($this->traefikConfig->getPidFile());
        $traefikStatus = $this->isProcessRunning($traefikPid) ? 'running' : 'stopped';

        $table = new Table($output);
        $table->setStyle('box');
        $table->setHeaderTitle('rooter');
        $table->setHeaders(['name', 'status', 'pid']);
        $table->setRows([
            ['dnsmasq', $dnsmasqStatus, $dnsmasqPid],
            new TableSeparator(),
            ['traefik', $traefikStatus, $traefikPid],

        ]);
        $table->render();

        $listEnvCommand = new ListEnvCommand();
        $listEnvCommand->run(new ArrayInput([]), $output);

        return 0;
    }

    private function getPidFromFile(string $pidFile): string
    {
        return trim(file_get_contents($pidFile));
    }

    private function isProcessRunning(string $pid): bool
    {
        if (empty($pid)) {
            return false;
        }
        return posix_kill((int)$pid, 0);
    }
}
