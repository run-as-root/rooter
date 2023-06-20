<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use RunAsRoot\Rooter\Config\DevenvConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class StartCommand extends Command
{
    private DevenvConfig $devenvConfig;
    private string $phpBin;
    private string $phpIniScanDir;

    public function configure()
    {
        $this->setName('env:start');
        $this->setDescription('start environment process');
        $this->addOption('debug','', InputOption::VALUE_NONE, 'activate debug mode');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->devenvConfig = new DevenvConfig();
        $phpBin = exec('which php');
        $this->phpBin = realpath($phpBin);
        $this->phpIniScanDir = dirname($this->phpBin, 2) . "/lib";
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

        // Launch the process
        $debug = $input->getOption('debug');

        $command = "devenv up";
        $process = $this->getProcess($command, $debug);

        $output->writeln("<info>environment starting …</info>");

        if (!$debug) // Initialize the progress bar
        {
            $process->start();

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

        } else {
            $process->run();
        }

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

    private function getProcess(string $command, bool $debug = false): Process
    {
        // ROOTER uses a specific PHP version which may not match the one from the env
        // here me make sure that the correct PHP_BIN and PHP_INI_SCAN_DIR is set
        // We need to preserve the env from the project and not use rooter env
        // ROOTER assumes the nginx config has been placed
        // @todo find a way to call nginx:init here: main question how do we know the envType e.g. magento2 (ENV var?)
        $command = "export PHP_BIN=\"$this->phpBin\" PHP_INI_SCAN_DIR=\"$this->phpIniScanDir\" ROOTER_INIT_SKIP=1 && " . $command;

        if (!$debug) {
            $command = sprintf('%s > %s 2>&1', $command, $this->devenvConfig->getLogFile());
        }

        $process = Process::fromShellCommandline($command, getcwd());
        $process->setTimeout(0);
        $process->setOptions(['create_new_console' => 1]);
        if ($debug) {
            $process->setTty(true);
        }

        return $process;
    }
}
