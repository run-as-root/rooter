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
        $this->addUsage('dump-1686574009.sql');
        $this->addUsage('dump-1686574009.sql --drop');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $PV_BIN = ROOTER_HOME_DIR . "/bin/pv";
        $GZIP_BIN = ROOTER_HOME_DIR . "/bin/gzip";

        $dbDumpFile = $input->getArgument('file');

        $DEVENV_DB_USER = getenv('DEVENV_DB_USER');
        $DEVENV_DB_PASS = getenv('DEVENV_DB_PASS');
        $DEVENV_DB_PORT = getenv('DEVENV_DB_PORT');
        $DEVENV_DB_NAME = getenv('DEVENV_DB_NAME');

        // Validate
        if (!is_file($dbDumpFile)) {
            $output->writeln("<error>File does not exist: $dbDumpFile</error>");

            return 1;
        }

        $mysqlParams = "-u$DEVENV_DB_USER -p$DEVENV_DB_PASS --host=localhost --port=$DEVENV_DB_PORT";

        // Drop Database
        if ($input->getOption('drop')) {
            exec("mysql $mysqlParams -e \"DROP DATABASE IF EXISTS $DEVENV_DB_NAME; CREATE DATABASE IF NOT EXISTS $DEVENV_DB_NAME;\"");
        }

        // Build Mysql Command
        $ext = pathinfo($dbDumpFile, PATHINFO_EXTENSION);
        if ($ext === 'gz') {
            $mySqlCommand = "mysql $mysqlParams --database=$DEVENV_DB_NAME";
            $command = "$PV_BIN -cN gzip " . escapeshellarg($dbDumpFile) . " | $GZIP_BIN -d | $PV_BIN -cN mysql | " . $mySqlCommand;
        } elseif ($ext === 'sql') {
            $command = "mysql $mysqlParams --database=$DEVENV_DB_NAME < $dbDumpFile";
        } else {
            $output->writeln("<error>Unsupported file type '$ext'</error>");

            return 1;
        }

        // Import
        $output->writeln("Starting Database Import of $dbDumpFile");
        $output->writeln("\n");

        exec($command);

        $output->writeln('');
        $output->writeln("import finished");

        return 0;
    }
}
