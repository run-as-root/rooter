<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use RunAsRoot\Rooter\Cli\Command\StartCommand as StartRooterCommand;
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
        private readonly StartRooterCommand $startRooterCommand,
        private readonly RegisterEnvCommand $registerEnvCommand,
        private readonly EnvironmentRepository $environmentRepository,
        private readonly ProcessComposeStartUpRenderer $processComposeStartUpRenderer,
        private readonly LogFileRenderer $logFileRenderer,
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('env:start');
        $this->setDescription('start environment process');
        $this->addOption('debug', '', InputOption::VALUE_NONE, 'activate debug mode');
    }

    /**
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $debug = $input->getOption('debug');

        if ($this->processManager->isRunning($this->devenvConfig->getPidFile())) {
            $output->writeln("environment is already running");
            return Command::FAILURE;
        }

        if (!is_dir(ROOTER_PROJECT_DIR)
            && !mkdir(ROOTER_PROJECT_DIR, 0755, true) && !is_dir(ROOTER_PROJECT_DIR)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', ROOTER_PROJECT_DIR));
        }

        // Initialisation
        // Register Environment Config
        $this->registerEnvCommand->run(new ArrayInput([]), $output);

        // Start rooter
        $this->startRooterCommand->run(new ArrayInput([]), $output);

        // initialise nginx conf for environment
        // @todo atm all environments are using nginx. environments using something else are currently not supported
//        $initNginx = new InitNginxCommand();
//        $initNginx->run(new ArrayInput([]), $output);

        // ROOTER assumes the nginx config has been placed
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
