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
        $type = getenv('ROOTER_ENV_TYPE') ?? '';
        $projectName = getenv('PROJECT_NAME');
        $devenvProfile = getenv('DEVENV_PROFILE');

        if ($this->processManager->isRunning($this->devenvConfig->getPidFile())) {
            $output->writeln("environment $projectName is already running");
            return Command::FAILURE;
        }

        $debug = $input->getOption('debug');
        $output->writeln("Starting environment $projectName");

        // Start rooter
        $this->startRooterCommand->run(new ArrayInput([]), $output);

        if (empty($projectName) || empty($devenvProfile)) {
            $output->writeln("<error>It seems the project is not initialised or setup correctly.</error>");
            $output->writeln("Did you run direnv allow .?");
            return Command::FAILURE;
        }

        // Register Environment Config
        $this->registerEnvCommand->run(new ArrayInput([]), $output);

        // Nginx init
        if (!empty($type)) {
            $initNginx = $this->initNginxCommand;
            $initNginx->run(new ArrayInput(['type' => $type]), $output);
        }

        // Start devenv environment
        $background = $debug ? '' : '--detach';
        $command = "devenv processes up $background";
        $command = "export ROOTER_INIT_SKIP=1 && " . $command;

        $output->writeln("environment processes starting …");

        // Launch the process
        $this->processManager->run($command, true);

        if ($debug) {
            // We are done in debug mode, since the process is running in foreground
            return Command::SUCCESS;
        }

        $envData = $this->environmentRepository->getByName($projectName);

        $isSuccess = $this->processComposeStartUpRenderer->render($envData, $output);

        $pid = $this->processManager->getPidFromFile($this->devenvConfig->getPidFile());

        $output->writeln("devenv is running with PID: $pid");
        $output->writeln("process-compose is running with socket: {$envData['processComposeSocket']}" );
        if (!$isSuccess) {
            $output->writeln(
                "<comment>not all processes started correctly, run `rooter env:process-compose` or `rooter env:status` to see details</comment>"
            );
        }
        $output->writeln("<info>environment started</info>");

        return $isSuccess ? Command::SUCCESS : Command::FAILURE;
    }

}
