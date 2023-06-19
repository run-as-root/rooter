<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use RunAsRoot\Rooter\Config\DevenvConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StopCommand extends Command
{
    private DevenvConfig $devenvConfig;

    public function configure()
    {
        $this->setName('env:stop');
        $this->setDescription('Stop environment');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->devenvConfig = new DevenvConfig();
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pidFile = $this->devenvConfig->getPidFile();

        $pid = $this->getPidFromFile($pidFile);
        if ($pid <= 0) {
            $output->writeln("<error>environment is not running for PID:$pid</error>");

            return 1;
        }

        $output->writeln("environment process with PID:$pid stopping");

        if ($ok = proc_open(sprintf('kill -%d %d', 2, $pid), [2 => ['pipe', 'w']], $pipes)) {
            $ok = false === fgets($pipes[2]);
        }

        if (!$ok) {
            $output->writeln("<error>Could not stop environment with PID:$pid</error>");
            return Command::FAILURE;
        }

        sleep(2);

        if (is_file($pidFile)) {
            file_put_contents($pidFile, '');
        }

        $output->writeln("environment process with PID:$pid was stopped");

        return 0;
    }

    private function getPidFromFile(string $pidFile): string
    {
        return trim(file_get_contents($pidFile));
    }
}
