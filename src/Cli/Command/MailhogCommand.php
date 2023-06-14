<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MailhogCommand extends Command
{
    public function configure()
    {
        $this->setName('mailhog');
        $this->setDescription('launch mailhog http UI');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectName = $_ENV['PROJECT_NAME']; # @todo check if set

        shell_exec("open 'http://$projectName-mailhog.rooter.test'");

        return 0;
    }
}
