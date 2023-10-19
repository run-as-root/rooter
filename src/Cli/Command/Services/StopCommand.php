<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Services;

use RunAsRoot\Rooter\Config\DevenvConfig;
use RunAsRoot\Rooter\Config\DnsmasqConfig;
use RunAsRoot\Rooter\Config\TraefikConfig;
use RunAsRoot\Rooter\Exception\FailedToStopProcessException;
use RunAsRoot\Rooter\Exception\ProcessNotRunningException;
use RunAsRoot\Rooter\Manager\ProcessManager;
use RunAsRoot\Rooter\Repository\EnvironmentRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StopCommand extends Command
{
    public function __construct(
        private readonly ProcessManager $processManager,
        private readonly EnvironmentRepository $envRepository,
        private readonly DevenvConfig $devenvConfig,
        private readonly DnsmasqConfig $dnsmasqConfig,
        private readonly TraefikConfig $traefikConfig
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('services:stop');
        $this->setDescription('stop rooter processes');
        $this->addOption('all', '', InputOption::VALUE_NONE, 'Stop all environments');
    }

    /**
     * @throws ExceptionInterface
     * @throws \JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = true;

        $result = $result && $this->stopProcess($this->dnsmasqConfig->getPidFile(), 'dnsmasq', $output);

        $result = $result && $this->stopProcess($this->traefikConfig->getPidFile(), 'traefik', $output);

        if ($input->getOption('all')) {
            $result = $result && $this->stopEnvironments($output);
        }

        return $result ? Command::SUCCESS : Command::FAILURE;
    }

    private function stopEnvironments(OutputInterface $output): bool
    {
        $result = true;
        foreach ($this->envRepository->getList() as $envData) {
            $name = $envData['name'];
            $path = $envData['path'];

            $pidFile = $this->devenvConfig->getPidFile($path);

            $result = $result && $this->stopProcess($pidFile, $name, $output);
        }
        return $result;
    }

    private function stopProcess(string $pidFile, string $name, OutputInterface $output): bool
    {
        $result = true;
        try {
            $this->processManager->stop($pidFile);
            $output->writeln("<info>$name was stopped</info>");
        } catch (ProcessNotRunningException $e) {
            $output->writeln("$name already stopped");
        } catch (FailedToStopProcessException $e) {
            $output->writeln("<error>$name could not be stopped: {$e->getMessage()}</error>");
            $result = false;
        } catch (\Exception $e) {
            $output->writeln("<error>$name unknown error: {$e->getMessage()}</error>");
            $result = false;
        }
        return $result;
    }
}
