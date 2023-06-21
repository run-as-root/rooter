<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Dnsmasq;

use RunAsRoot\Rooter\Config\DnsmasqConfig;
use RunAsRoot\Rooter\Manager\ProcessManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StopDnsmasqCommand extends Command
{
    private DnsmasqConfig $dnsmasqConfig;
    private ProcessManager $processManager;

    public function configure()
    {
        $this->setName('dnsmasq:stop');
        $this->setDescription('Stop dnsmasq');
        $this->setHidden();
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->dnsmasqConfig = new DnsmasqConfig();
        $this->processManager = new ProcessManager();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pidFile = $this->dnsmasqConfig->getPidFile();

        $this->processManager->stop($pidFile);
        $output->writeln("<info>dnsmasq was stopped</info>");

        return 0;
    }
}
