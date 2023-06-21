<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command;

use RunAsRoot\Rooter\Cli\Command\Env\ListEnvCommand;
use RunAsRoot\Rooter\Config\DnsmasqConfig;
use RunAsRoot\Rooter\Config\TraefikConfig;
use RunAsRoot\Rooter\Manager\ProcessManager;
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
    private ProcessManager $processManager;

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
        $this->processManager = new ProcessManager();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dnsmasqPid = $this->processManager->getPidFromFile($this->dnsmasqConfig->getPidFile());
        $dnsmasqStatus = $this->processManager->isRunningByPid($dnsmasqPid) ? 'running' : 'stopped';

        $traefikPid = $this->processManager->getPidFromFile($this->traefikConfig->getPidFile());
        $traefikStatus = $this->processManager->isRunningByPid($traefikPid) ? 'running' : 'stopped';

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

}
