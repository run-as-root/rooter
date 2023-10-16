<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SelfUpdateCommand extends Command
{
    public function configure()
    {
        $this->setName('selfupdate');
        $this->setDescription('updates the rooter installation (available only in flake dist)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (getenv('ROOTER_APP_MODE') !== 'production') {
            $output->writeln('<error>This command is only available in production mode</error>');
            return Command::FAILURE;
        }

        shell_exec('nix profile upgrade ".*.rooter"');

        return Command::SUCCESS;
    }
}
