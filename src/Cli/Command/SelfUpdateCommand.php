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

        $nixVersion = shell_exec('nix --version');
        $nixVersion = preg_match('/nix .* ([0-9.]+\.[0-9.]+\.[0-9.]+)/', $nixVersion, $matches) ? $matches[1] : 'unknown';

        // forces a re-download of the phar by setting ttl to 0
        if (version_compare($nixVersion, '2.20.0', '<')) {
            shell_exec('nix profile upgrade ".*.rooter" --tarball-ttl 0');
        } else {
            shell_exec('nix profile upgrade rooter --tarball-ttl 0');
        }

        return Command::SUCCESS;
    }
}
