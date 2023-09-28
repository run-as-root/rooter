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
    public function __construct(
        private readonly TraefikConfig $traefikConfig,
        private readonly ProcessManager $processManager,
        private readonly DnsmasqConfig $dnsmasqConfig
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('start');
        $this->setDescription('start rooter processes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Dnsmasq
        try {
            $command = $this->dnsmasqConfig->getDnsmasqCommand();
            $pidFile = $this->dnsmasqConfig->getPidFile();

            $output->writeln("dnsmasq command: $command", OutputInterface::VERBOSITY_VERBOSE);
            $output->writeln("dnsmasq pidFile: $pidFile", OutputInterface::VERBOSITY_VERBOSE);

            $this->processManager->startWithPid($command, $pidFile);

            $output->writeln("<info>dnsmasq started.</info>");
        } catch (ProcessAlreadyRunningException $e) {
            $output->writeln("dnsmasq is already running ({$e->getMessage()})");
        }

        // Traefik
        try {
            $command = $this->traefikConfig->getTraefikCommand();
            $pidFile = $this->traefikConfig->getPidFile();

            $output->writeln("traefik command: $command", OutputInterface::VERBOSITY_VERBOSE);
            $output->writeln("traefik pidFile: $pidFile", OutputInterface::VERBOSITY_VERBOSE);

            $this->processManager->startWithPid($command, $pidFile);

            $output->writeln("<info>traefik started.</info>");
        } catch (ProcessAlreadyRunningException $e) {
            $output->writeln("traefik is already running ({$e->getMessage()})");
        }

        return 0;
    }
}
