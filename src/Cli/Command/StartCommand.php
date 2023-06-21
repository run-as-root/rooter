<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command;

use RunAsRoot\Rooter\Config\DnsmasqConfig;
use RunAsRoot\Rooter\Config\TraefikConfig;
use RunAsRoot\Rooter\Exception\ProcessAlreadyRunningException;
use RunAsRoot\Rooter\Manager\ProcessManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StartCommand extends Command
{
    private TraefikConfig $traefikConfig;
    private ProcessManager $processManager;
    private DnsmasqConfig $dnsmasqConfig;

    public function configure()
    {
        $this->setName('start');
        $this->setDescription('start rooter processes');
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
        // Dnsmasq
        try {
            $this->processManager->startWithPid($this->dnsmasqConfig->getDnsmasqCommand(), $this->dnsmasqConfig->getPidFile());
            $output->writeln("<info>dnsmasq started.</info>");
        } catch (ProcessAlreadyRunningException $e) {
            $output->writeln("dnsmasq is already running ({$e->getMessage()})");
        }

        // Traefik
        try {
            $this->processManager->startWithPid($this->traefikConfig->getTraefikCommand(), $this->traefikConfig->getPidFile());
            $output->writeln("<info>traefik started.</info>");
        } catch (ProcessAlreadyRunningException $e) {
            $output->writeln("traefik is already running ({$e->getMessage()})");
        }

        return 0;
    }
}
