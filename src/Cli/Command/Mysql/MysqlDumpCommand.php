<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Mysql;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class MysqlDumpCommand extends Command
{
    public function configure()
    {
        $this->setName('mysql:dump');
        $this->setDescription('dump mysql database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = getenv('DEVENV_DB_USER');
        $pass = getenv('DEVENV_DB_PASS');
        $port = getenv('DEVENV_DB_PORT');
        $db = getenv('DEVENV_DB_NAME');

        $dumpFile = sprintf("dump-%s.sql", time());
        $command = "mysqldump -u$user -p$pass --host=127.0.0.1 --port=$port $db > $dumpFile";

        $output->writeln($command);

        $exitCode = Process::fromShellCommandline(command: $command, timeout: 120)->setTty(true)->run();

        $output->writeln("dumped to $dumpFile");

        return $exitCode;
    }
}
