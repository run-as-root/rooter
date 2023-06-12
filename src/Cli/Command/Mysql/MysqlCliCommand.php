<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Mysql;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class MysqlCliCommand extends Command
{
    public function configure()
    {
        $this->setName('mysql:cli');
        $this->setDescription('run interactive mysql commands');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = getenv('DEVENV_DB_USER');
        $pass = getenv('DEVENV_DB_PASS');
        $port = getenv('DEVENV_DB_PORT');
        $db = getenv('DEVENV_DB_NAME');

        $command = "mysql -A -u$user -p$pass --host=127.0.0.1 --port=$port --database=$db";

        $output->writeln($command);

        return Process::fromShellCommandline($command)->setTty(true)->run();
    }
}
