<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use RunAsRoot\Rooter\Cli\Command\StartCommand as StartRooterCommand;
use RunAsRoot\Rooter\Config\DevenvConfig;
use RunAsRoot\Rooter\Manager\ProcessManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Helper\ProgressBar;
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
        private readonly RegisterEnvCommand $registerEnvCommand
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

        $pidFile = $this->devenvConfig->getPidFile();
        if ($this->processManager->isRunning($pidFile)) {
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

        $output->writeln("<info>environment starting …</info>");

        // Launch the process
        if ($debug) {
            // We are done in debug mode, since the process is running in foreground
            $this->processManager->run($command, true);
            return Command::SUCCESS;
        }

        $this->processManager->start($command, true);

        // Taking the pid from devenv file here, since the one returned from symfony process is only the spawning process
        while (!$this->processManager->hasPid($pidFile)) {
            usleep(500000); // Sleep for 0.5 seconds
        }

        $this->renderLogOutput($this->devenvConfig->getLogFile(), $output);

        $pid = $this->processManager->getPidFromFile($pidFile);

        $output->writeln('');
        $output->writeln("<info>devenv is running with PID:$pid</info>");

        return Command::SUCCESS;
    }

    /**
     * Reads the log file and outputs it to the console as long as new content is written to the file
     * If no new content is written for 3 seconds, the output is stopped
     */
    private function renderLogOutput($logFilePath, OutputInterface $output): void
    {
        $file = fopen($logFilePath, 'rb');
        if (!$file) {
            throw new \RuntimeException("File $logFilePath does not exist");
        }

        $lastPosition = $unchangedCounter = 0;

        fseek($file, $lastPosition);

        while (true) {
            clearstatcache();
            $currentSize = filesize($logFilePath);

            if ($currentSize > $lastPosition) {
                $file = fopen($logFilePath, 'rb');
                if (!$file) {
                    $output->writeln('<error>Unable to open the log file.</error>');
                    break;
                }

                fseek($file, $lastPosition);

                while (!feof($file)) {
                    $line = fgets($file);
                    if ($line === false || str_contains($line, 'declare -x')) {
                        continue;
                    }
                    $output->write($line);
                }

                $lastPosition = ftell($file);

                fclose($file);
            } else {
                $unchangedCounter++;
            }

            sleep(1); // Sleep for 1 second

            if ($unchangedCounter > 3) {
                break;
            }
        }
    }

}
