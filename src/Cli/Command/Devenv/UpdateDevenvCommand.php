<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Devenv;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateDevenvCommand extends Command
{
    public function configure()
    {
        $this->setName('devenv:update');
        $this->setDescription('run devenv update for the current environment');
        $this->setHidden();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        shell_exec("devenv update");

        return Command::SUCCESS;
    }
}
