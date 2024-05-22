<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use Exception;
use RunAsRoot\Rooter\Cli\Command\Services\StopServicesCommand;
use RunAsRoot\Rooter\Cli\Command\Traefik\RemoveTraefikConfigCommand;
use RunAsRoot\Rooter\Repository\EnvironmentRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class StopCommand extends Command
{
    public function __construct(
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
            $resultEnv = $this->stopEnvironment($envData, $output);

            $input = new ArrayInput(['name' => $envData['name']]);

            $resultTraefik = $this->removeTraefikConfigCommand->run($input, $output) === Command::SUCCESS;

            $result = $result && $resultEnv && $resultTraefik;
        }
        return $result;
    }

    private function stopEnvironment(array $envData, OutputInterface $output): bool
    {
        $result = true;
        $name = $envData['name'];
        $path = $envData['path'];
        /** @var ConsoleSectionOutput $section */
        $section = $output->section();
        try {
            $section->writeln("$name stopping ...");

            $process = Process::fromShellCommandline("devenv processes down", $path);
            $process->disableOutput();
            $process->setTimeout(0);
            $process->setOptions(['create_new_console' => 1]);
            $process->setTty(true);
            $process->run();

            $exitCode = $process->getExitCode();

            $msg = 'stopped';
            if ($exitCode === 1) {
                $msg = "No processes running";
            }

            $section->overwrite("<info>$name stopped</info> ($exitCode: $msg)");
        } catch (Exception $e) {
            $output->writeln("<error>$name error: {$e->getMessage()}</error>");
            $result = false;
        }
        return $result;
    }
}
