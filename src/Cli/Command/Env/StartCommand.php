<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use RunAsRoot\Rooter\Config\DevenvConfig;
use RunAsRoot\Rooter\Manager\ProcessManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StartCommand extends Command
{
    private DevenvConfig $devenvConfig;
    private ProcessManager $processManager;

    public function configure()
    {
        $this->setName('env:start');
        $this->setDescription('start environment process');
        $this->addOption('debug', '', InputOption::VALUE_NONE, 'activate debug mode');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->devenvConfig = new DevenvConfig();
        $this->processManager = new ProcessManager();
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

        // Show progress bar until we have a devenv pid
        $progressBar = $this->getProgressBar($output);
        $progressBar->start();

        // Taking the pid from devenv file here, since the one returned from symfony process is only the spawning process
        while (!$this->processManager->hasPid($pidFile)) {
            usleep(500000); // Sleep for 0.5 seconds
            $progressBar->advance();
        }
        $progressBar->finish();

        $pid = $this->processManager->getPidFromFile($pidFile);

        $output->writeln('');
        $output->writeln("<info>devenv is running with PID:$pid</info>");

        return Command::SUCCESS;
    }

    private function getProgressBar(OutputInterface $output): ProgressBar
    {
        $progressBar = new ProgressBar($output);
        $progressBar->setFormat("%current%/%max% [%bar%] %elapsed:6s% %memory:6s%");
        $progressBar->setBarCharacter('█');
        $progressBar->setEmptyBarCharacter('░');
        $progressBar->setProgressCharacter('▒');
        $progressBar->setBarWidth(50);
        $progressBar->setRedrawFrequency(10);
        return $progressBar;
    }

}
