<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use Exception;
use RunAsRoot\Rooter\Cli\Command\Services\StopServicesCommand;
use RunAsRoot\Rooter\Cli\Command\Traefik\RemoveTraefikConfigCommand;
use RunAsRoot\Rooter\Config\DevenvConfig;
use RunAsRoot\Rooter\Exception\FailedToStopProcessException;
use RunAsRoot\Rooter\Exception\ProcessNotRunningException;
use RunAsRoot\Rooter\Manager\ProcessManager;
use RunAsRoot\Rooter\Repository\EnvironmentRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StopCommand extends Command
{
    public function __construct(
        private readonly DevenvConfig $devenvConfig,
        private readonly ProcessManager $processManager,
        private readonly EnvironmentRepository $envRepository,
        private readonly RemoveTraefikConfigCommand $removeTraefikConfigCommand,
        private readonly StopServicesCommand $stopServicesCommand,
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('stop');
        $this->setAliases(['env:stop']);
        $this->setDescription('Stop environment(s)');
        $this->addArgument('name', InputArgument::OPTIONAL, 'The name of the environment you want to stop');
        $this->addOption('all', '', InputOption::VALUE_NONE, 'Stop all environments');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stopAll = $input->getOption('all');

        $environments = [];
        if (!$stopAll) {
            $projectName = $input->getArgument('name') ?: getenv('PROJECT_NAME');
            $environments[] = $this->envRepository->getByName($projectName);
        } else {
            $output->writeln("Stopping all environments");
            $environments = $this->envRepository->getList();
        }

        $result = $this->stopEnvironments($environments, $output);

        if ($stopAll) {
            $resultStopServices = $this->stopServicesCommand->run(new ArrayInput([]), $output);
            $result = $result && ($resultStopServices === Command::SUCCESS);
        }

        return $result ? Command::SUCCESS : Command::FAILURE;
    }

    private function stopEnvironments(array $environments, OutputInterface $output): bool
    {
        $result = true;
        foreach ($environments as $envData) {
            $name = $envData['name'];
            $path = $envData['path'];

            $pidFile = $this->devenvConfig->getPidFile($path);

            $resultEnv = $this->stopEnvironment($pidFile, $name, $output);

            $resultTraefik = $this->removeTraefikConfigCommand->run(new ArrayInput(['name' => $name]), $output) === Command::SUCCESS;

            $result = $result && $resultEnv && $resultTraefik;
        }
        return $result;
    }

    private function stopEnvironment(string $pidFile, string $name, OutputInterface $output): bool
    {
        $result = true;
        try {
            $output->writeln("$name stopping ...");
            $this->processManager->stop($pidFile);
            $output->writeln("<info>$name stopped</info>");
        } catch (ProcessNotRunningException $e) {
            $output->writeln("$name already stopped");
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
