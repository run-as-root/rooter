<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ProcessComposeCommand extends Command
{
    public function configure()
    {
        $this->setName('env:process-compose');
        $this->setDescription('Attach to the process-compose instance of this environment');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $socket = getenv('PC_SOCKET_PATH');

        $command = "process-compose -u $socket attach";

        return Process::fromShellCommandline($command)->setTty(true)->run();
    }
}
