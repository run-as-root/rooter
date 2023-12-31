<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MailCommand extends Command
{
    public function configure()
    {
        $this->setName('mail');
        $this->setDescription('launch mail http UI in browser');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectName = getenv('PROJECT_NAME');

        if (empty($projectName)) {
            $output->writeln("<error>PROJECT_NAME is not set. This command should be executed in a project context.</error>");
            return Command::FAILURE;
        }

        shell_exec("open 'http://$projectName-mail.rooter.test'");

        return 0;
    }
}
