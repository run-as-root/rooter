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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StatusCommand extends Command
{
    public function __construct(
        private readonly ListEnvCommand $listEnvCommand,
        private readonly ProcessManager $processManager,
        private readonly DnsmasqConfig $dnsmasqConfig,
        private readonly TraefikConfig $traefikConfig
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('status');
        $this->setDescription('show status of rooter');
        $this->addOption('ports', '', InputOption::VALUE_NONE, 'show all ports');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $showPorts = $input->getOption('ports');

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

        $this->listEnvCommand->run(new ArrayInput(['--ports' => $showPorts]), $output);

        return 0;
    }

}
