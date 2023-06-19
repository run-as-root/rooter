<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Magento2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class RefreshMagento2DbCommand extends Command
{
    private string $phpBin;
    private string $phpIniScanDir;

    public function configure()
    {
        $this->setName('magento2:db-refresh');
        $this->setDescription('Refresh database from dump');
        $this->addArgument('dump-file', InputArgument::REQUIRED, 'path to db dump');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $phpBin = exec('which php');
        $this->phpBin = realpath($phpBin);
        $this->phpIniScanDir = dirname($this->phpBin, 2) . "/lib";
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dbDumpFile = $input->getArgument('dump-file');

        $MAGERUN2_BIN = getenv('MAGERUN2_BIN') ?: 'n98-magerun2';
        $DEVENV_DB_USER = getenv('DEVENV_DB_USER');
        $DEVENV_DB_PASS = getenv('DEVENV_DB_PASS');
        $DEVENV_DB_PORT = getenv('DEVENV_DB_PORT');
        $DEVENV_DB_NAME = getenv('DEVENV_DB_NAME');

        $mysqlParams = "-u$DEVENV_DB_USER -p$DEVENV_DB_PASS --host=localhost --port=$DEVENV_DB_PORT";

        exec("mysql $mysqlParams -e \"DROP DATABASE IF EXISTS $DEVENV_DB_NAME; CREATE DATABASE IF NOT EXISTS $DEVENV_DB_NAME;\"");

        exec("mysql $mysqlParams --database=$DEVENV_DB_NAME < $dbDumpFile");

        $command = "$this->phpBin bin/magento setup:upgrade";
        $this->runCommand($command);

        $command = "$this->phpBin bin/magento config:data:import config/store dev/rooter";
        $this->runCommand($command);

        $command = "$MAGERUN2_BIN admin:user:delete -f -n admin";
        $this->runCommand($command);

        $command = "$MAGERUN2_BIN admin:user:create --admin-user=admin --admin-password=admin123 --admin-email=admin@run-as-root.sh --admin-firstname=Admin --admin-lastname=Admin";
        $this->runCommand($command);

        // Reindex so Elasticsearch gets the updated data
        $command = "$this->phpBin bin/magento indexer:reindex";
        $this->runCommand($command);

        return 0;
    }

    private function runCommand(string $command): int
    {
        // We need to preserve the env from the project and not use rooter env
        $envVars = ['PHP_BIN' => $this->phpBin, 'PHP_INI_SCAN_DIR' => $this->phpIniScanDir];
        $process = Process::fromShellCommandline($command, getcwd(), $envVars);
        $result = $process->setTty(true)->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $result;
    }
}
