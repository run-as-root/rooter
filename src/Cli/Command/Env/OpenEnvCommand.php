<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OpenEnvCommand extends Command
{
    public function configure()
    {
        $this->setName('open');
        $this->setAliases(['launch', 'env:open']);
        $this->setDescription('open the HTTP(s) environment in the browser');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectHost = getenv('PROJECT_HOST');

        if (empty($projectHost)) {
            $output->writeln("<error>PROJECT_HOST is not set. This command should be executed in a project context.</error>");
            return Command::FAILURE;
        }

        shell_exec("open 'https://$projectHost'");

        return 0;
    }
}
