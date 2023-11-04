<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Services;

use Exception;
use JsonException;
use RunAsRoot\Rooter\Config\DnsmasqConfig;
use RunAsRoot\Rooter\Config\TraefikConfig;
use RunAsRoot\Rooter\Exception\FailedToStopProcessException;
use RunAsRoot\Rooter\Exception\ProcessNotRunningException;
use RunAsRoot\Rooter\Manager\ProcessManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;

class StopServicesCommand extends Command
{
    public function __construct(
        private readonly ProcessManager $processManager,
        private readonly DnsmasqConfig $dnsmasqConfig,
        private readonly TraefikConfig $traefikConfig
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('services:stop');
        $this->setDescription('stop rooter processes');
    }

    /**
     * @throws ExceptionInterface
     * @throws JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = true;

        $output->writeln("Stopping all services");
        $result = $result && $this->stopProcess($this->dnsmasqConfig->getPidFile(), 'dnsmasq', $output);

        $result = $result && $this->stopProcess($this->traefikConfig->getPidFile(), 'traefik', $output);

        return $result ? Command::SUCCESS : Command::FAILURE;
    }

    private function stopProcess(string $pidFile, string $name, OutputInterface $output): bool
    {
        $result = true;
        /** @var ConsoleSectionOutput $section */
        $section = $output->section();
        try {
            $section->writeln("$name stopping");
            $this->processManager->stop($pidFile);
            $section->overwrite("<info>$name stopped</info>");
        } catch (ProcessNotRunningException $e) {
            $section->overwrite("$name already stopped");
        } catch (FailedToStopProcessException $e) {
            $output->writeln("<error>$name could not be stopped: {$e->getMessage()}</error>");
            $result = false;
        } catch (Exception $e) {
            $output->writeln("<error>$name unknown error: {$e->getMessage()}</error>");
            $result = false;
        }
        return $result;
    }
}
