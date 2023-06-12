<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class QueriousCommand extends Command
{
    public function configure()
    {
        $this->setName('querious');
        $this->setDescription('launch Querious MacOS App');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = getenv('DEVENV_DB_USER');
        $pass = getenv('DEVENV_DB_PASS');
        $port = getenv('DEVENV_DB_PORT');
        $db = getenv('DEVENV_DB_NAME');

        $link = "querious://connect/new?host=127.0.0.1&user=$user&password=$pass&database=$db&port=$port&use-compression=false";

        shell_exec("open \"$link\"");

        return 0;
    }
}
