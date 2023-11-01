<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use RunAsRoot\Rooter\Cli\Command\Nginx\InitNginxCommand;
use RunAsRoot\Rooter\Cli\Command\Services\StartServicesCommand;
use RunAsRoot\Rooter\Cli\Output\LogFileRenderer;
use RunAsRoot\Rooter\Cli\Output\ProcessComposeStartUpRenderer;
use RunAsRoot\Rooter\Config\DevenvConfig;
use RunAsRoot\Rooter\Manager\ProcessManager;
use RunAsRoot\Rooter\Repository\EnvironmentRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StartCommand extends Command
{
    public function __construct(
        private readonly ProcessManager $processManager,
        private readonly DevenvConfig $devenvConfig,
        private readonly StartServicesCommand $startRooterCommand,
        private readonly RegisterEnvCommand $registerEnvCommand,
        private readonly InitNginxCommand $initNginxCommand,
        private readonly EnvironmentRepository $environmentRepository,
        private readonly ProcessComposeStartUpRenderer $processComposeStartUpRenderer,
        private readonly LogFileRenderer $logFileRenderer,
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('start');
        $this->setAliases(['env:start']);
        $this->setDescription('start environment process');
        $this->addOption('debug', '', InputOption::VALUE_NONE, 'activate debug mode');
    }

    /**
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectName = getenv('PROJECT_NAME');

        if ($this->processManager->isRunning($this->devenvConfig->getPidFile())) {
            $output->writeln("environment $projectName is already running");
            return Command::FAILURE;
        }

        $debug = $input->getOption('debug');
        $output->writeln("Starting environment $projectName");

        // Start rooter
        $this->startRooterCommand->run(new ArrayInput([]), $output);

        // Register Environment Config
        $this->registerEnvCommand->run(new ArrayInput([]), $output);

        // Nginx init
        $type = getenv('ROOTER_ENV_TYPE') ?? '';
        if ($type) {
            $initNginx = $this->initNginxCommand;
            $initNginx->run(new ArrayInput(['type' => $type]), $output);
        }

        // Start devenv environment
        $command = "devenv up";
        if (!$debug) {
            $command = sprintf('%s > %s 2>&1', $command, $this->devenvConfig->getLogFile());
        }
        $command = "export ROOTER_INIT_SKIP=1 && " . $command;

        $output->writeln("environment initialising ...");

        // Launch the process
        if ($debug) {
            // We are done in debug mode, since the process is running in foreground
            $this->processManager->run($command, true);
            return Command::SUCCESS;
        }

        $this->processManager->start($command, true);

        $projectName = getenv('PROJECT_NAME');
        $envData = $this->environmentRepository->getByName($projectName);

        $processComposePort = $envData['processComposePort'];
        if (empty($processComposePort)) {
            $this->followStartLegacy($output);
            return Command::SUCCESS;
        }

        $isSuccess = $this->processComposeStartUpRenderer->render($envData, $output);

        $pid = $this->processManager->getPidFromFile($this->devenvConfig->getPidFile());

        $output->writeln("devenv is running with PID: $pid");
        $output->writeln("process-compose is running on port: $processComposePort");
        if (!$isSuccess) {
            $output->writeln(
                "<comment>not all processes started correctly, run `rooter process-compose` or `rooter env:status` to see details</comment>"
            );
        }
        $output->writeln("<info>environment started</info>");

        return $isSuccess ? Command::SUCCESS : Command::FAILURE;
    }

    /** @deprecated it was introduced as a fallback in the early stages */
    private function followStartLegacy(OutputInterface $output): void
    {
        $pidFile = $this->devenvConfig->getPidFile();
        // Taking the pid from devenv file here, since the one returned from symfony process is only the spawning process
        while (!$this->processManager->hasPid($pidFile)) {
            usleep(500000); // Sleep for 0.5 seconds
        }

        $this->logFileRenderer->render($this->devenvConfig->getLogFile(), $output);
    }

}
