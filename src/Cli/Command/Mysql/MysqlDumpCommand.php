<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Mysql;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class MysqlDumpCommand extends Command
{
    private const DEFAULT_DB_CMD_TIMEOUT = 360;

    public function configure()
    {
        $this->setName('mysql:dump');
        $this->setDescription('dump mysql database');
        $this->addOption('timeout', 't', InputOption::VALUE_REQUIRED, 'timeout in seconds');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = getenv('DEVENV_DB_USER');
        $pass = getenv('DEVENV_DB_PASS');
        $port = getenv('DEVENV_DB_PORT');
        $db = getenv('DEVENV_DB_NAME');
        $timeout = getenv('DEVENV_DB_CMD_TIMEOUT') ?: self::DEFAULT_DB_CMD_TIMEOUT;

        $dumpFile = sprintf("dump-%s.sql", time());
        $command = "mysqldump -u$user -p$pass --host=127.0.0.1 --port=$port $db > $dumpFile";

        $output->writeln($command);

        $exitCode = Process::fromShellCommandline(command: $command, timeout: $timeout)->setTty(true)->run();

        $output->writeln("dumped to $dumpFile");

        return $exitCode;
    }
}
