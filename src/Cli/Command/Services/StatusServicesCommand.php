<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Services;

use RunAsRoot\Rooter\Cli\Output\Style\TitleGrayOutputStyle;
use RunAsRoot\Rooter\Config\DnsmasqConfig;
use RunAsRoot\Rooter\Config\TraefikConfig;
use RunAsRoot\Rooter\Manager\ProcessManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class StatusServicesCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly ProcessManager $processManager,
        private readonly DnsmasqConfig $dnsmasqConfig,
        private readonly TraefikConfig $traefikConfig
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('services:status');
        $this->setDescription('show status of rooter');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dnsmasqPid = $this->processManager->getPidFromFile($this->dnsmasqConfig->getPidFile());
        $dnsmasqStatus = $this->processManager->isRunningByPid($dnsmasqPid) ? 'running' : 'stopped';

        $traefikPid = $this->processManager->getPidFromFile($this->traefikConfig->getPidFile());
        $traefikStatus = $this->processManager->isRunningByPid($traefikPid) ? 'running' : 'stopped';

        $this->io->block('services', null, TitleGrayOutputStyle::NAME, '  ', true);

        $table = new Table($output);
        $table->setStyle('box');
        $table->setHeaders(['name', 'status', 'pid']);
        $table->setRows([
            ['dnsmasq', $dnsmasqStatus, $dnsmasqPid],
            new TableSeparator(),
            ['traefik', $traefikStatus, $traefikPid],

        ]);
        $table->render();

        return Command::SUCCESS;
    }

}
