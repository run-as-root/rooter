<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ProcessComposeCommand extends Command
{
    public function configure()
    {
        $this->setName('process-compose');
        $this->setDescription('Attach to the process-compose instance of this environment');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $port = getenv('DEVENV_PROCESS_COMPOSE_PORT');

        $command = "process-compose -p $port attach";

        return Process::fromShellCommandline($command)->setTty(true)->run();
    }
}
