<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Mysql;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MysqlImportCommand extends Command
{
    public function configure()
    {
        $this->setName('mysql:import');
        $this->setDescription('import mysql database dump');
        $this->addArgument('file', InputArgument::REQUIRED, 'path to db dump');
        $this->addOption('drop', '', InputOption::VALUE_NONE, 'Drop and recreate database before import');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dbDumpFile = $input->getArgument('file');

        $DEVENV_DB_USER = getenv('DEVENV_DB_USER');
        $DEVENV_DB_PASS = getenv('DEVENV_DB_PASS');
        $DEVENV_DB_PORT = getenv('DEVENV_DB_PORT');
        $DEVENV_DB_NAME = getenv('DEVENV_DB_NAME');

        $mysqlParams = "-u$DEVENV_DB_USER -p$DEVENV_DB_PASS --host=localhost --port=$DEVENV_DB_PORT";

        if ($input->getOption('drop')) {
            exec("mysql $mysqlParams -e \"DROP DATABASE IF EXISTS $DEVENV_DB_NAME; CREATE DATABASE IF NOT EXISTS $DEVENV_DB_NAME;\"");
        }

        exec("mysql $mysqlParams --database=$DEVENV_DB_NAME < $dbDumpFile");

        $output->writeln("import finished");

        return 0;
    }
}
