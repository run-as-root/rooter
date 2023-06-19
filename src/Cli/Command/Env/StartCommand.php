<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use RunAsRoot\Rooter\Config\DevenvConfig;
use RunAsRoot\Rooter\Config\RooterConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class StartCommand extends Command
{
    private DevenvConfig $devenvConfig;

    public function configure()
    {
        $this->setName('env:start');
        $this->setDescription('start environment process');
    }
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->devenvConfig = new DevenvConfig();
    }
    /**
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pidFile = $this->devenvConfig->getPidFile();

        $pid = null;
        if (is_file($pidFile)) {
            $pid = trim(file_get_contents($pidFile));
        }
        if ($pid > 0) {
            $output->writeln("environment is already running with PID:$pid");
            return Command::FAILURE;
        }

        if (!is_dir(ROOTER_PROJECT_DIR)
            && !mkdir(ROOTER_PROJECT_DIR, 0755, true) && !is_dir(ROOTER_PROJECT_DIR)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', ROOTER_PROJECT_DIR));
        }

        $command = sprintf('devenv up > %s 2>&1', $this->devenvConfig->getLogFile());

        // Activate for debugging:
        // $process = new Process(['devenv', 'up']);
        // $process->setTty(true);

        // Launch the process
        $process = Process::fromShellCommandline($command);
        $process->setTimeout(0);
        $process->setOptions(['create_new_console' => 1]);

        $process->start();

        $output->writeln("<info>environment starting …</info>");

        // Initialize the progress bar
        $progressBar = $this->getProgressBar($output);

        $progressBar->start();
        while (!$this->hasPid($pidFile)) {
            usleep(500000); // Sleep for 0.5 seconds
            $progressBar->advance();
        }
        $progressBar->finish();
        $output->writeln('');

        $pid = $this->getPidFromFile($pidFile);

        $output->writeln("<info>devenv is running with PID:$pid</info>");

        return Command::SUCCESS;
    }

    private function hasPid(string $pidFile): bool
    {
        return file_exists($pidFile) && trim(file_get_contents($pidFile)) !== '';
    }

    private function getPidFromFile(string $pidFile): string
    {
        return trim(file_get_contents($pidFile));
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
