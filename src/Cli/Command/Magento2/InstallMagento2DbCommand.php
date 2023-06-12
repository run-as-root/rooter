<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Magento2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class InstallMagento2DbCommand extends Command
{
    public function configure()
    {
        $this->setName('magento2:db-install');
        $this->setDescription('Initialise fresh database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $phpPath = null;
        $phpBin = exec('which php', $phpPath);
        $phpBin = realpath($phpBin);
        $phpIniScanDir = dirname($phpBin, 2) . "/lib";

        $DEVENV_DB_USER = getenv('DEVENV_DB_USER');
        $DEVENV_DB_PASS = getenv('DEVENV_DB_PASS');
        $DEVENV_DB_PORT = getenv('DEVENV_DB_PORT');
        $DEVENV_DB_NAME = getenv('DEVENV_DB_NAME');
        $PROJECT_HOST = getenv('PROJECT_HOST');
        $DEVENV_REDIS_PORT = getenv('DEVENV_REDIS_PORT');
        $DEVENV_ELASTICSEARCH_PORT = getenv('DEVENV_ELASTICSEARCH_PORT');
        $DEVENV_AMQP_PORT = getenv('DEVENV_AMQP_PORT');
        $DEVENV_AMQP_USER = getenv('DEVENV_AMQP_USER');
        $DEVENV_AMQP_PASS = getenv('DEVENV_AMQP_PASS');

        $mysqlParams = "-u{$DEVENV_DB_USER} -p{$DEVENV_DB_PASS} --host=localhost --port={$DEVENV_DB_PORT}";

        exec("mysql $mysqlParams -e \"DROP DATABASE IF EXISTS $DEVENV_DB_NAME; CREATE DATABASE IF NOT EXISTS $DEVENV_DB_NAME;\"");

        $command = "$phpBin bin/magento setup:install \
                --db-host=127.0.0.1:$DEVENV_DB_PORT --db-name=$DEVENV_DB_NAME --db-user=$DEVENV_DB_USER --db-password=$DEVENV_DB_PASS \
                --admin-email=admin@mwltr.de --admin-firstname=Admin --admin-lastname=Admin --admin-password=admin123 --admin-user=admin \
                --backend-frontname=admin  \
                --base-url=http://$PROJECT_HOST/ \
                --currency=EUR --language=en_US --timezone=Europe/Berlin --ansi \
                --session-save=redis \
                --session-save-redis-host=127.0.0.1 \
                --session-save-redis-port=$DEVENV_REDIS_PORT \
                --session-save-redis-timeout=2.5 \
                --session-save-redis-db=2 \
                --cache-backend=redis \
                --cache-backend-redis-server=127.0.0.1 \
                --cache-backend-redis-db=0 \
                --cache-backend-redis-port=$DEVENV_REDIS_PORT  \
                --page-cache=redis \
                --page-cache-redis-server=127.0.0.1 \
                --page-cache-redis-db=1 \
                --page-cache-redis-port=$DEVENV_REDIS_PORT  \
                --elasticsearch-host=\"127.0.0.1\" \
                --elasticsearch-port=\"$DEVENV_ELASTICSEARCH_PORT\" \
                --elasticsearch-username=\"\" \
                --elasticsearch-password=\"\" \
                --amqp-host=127.0.0.1 --amqp-port=$DEVENV_AMQP_PORT --amqp-user=$DEVENV_AMQP_USER --amqp-password=$DEVENV_AMQP_PASS --amqp-virtualhost=\"/\"";
        $this->runCommand($command, $phpBin, $phpIniScanDir);

        $command = "$phpBin bin/magento setup:upgrade";
        $this->runCommand($command, $phpBin, $phpIniScanDir);

        $command = "$phpBin bin/magento config:data:import config/store dev/rooter";
        $this->runCommand($command, $phpBin, $phpIniScanDir);

        // Reindex so Elasticsearch gets the updated data
        $command = "$phpBin bin/magento indexer:reindex";
        $this->runCommand($command, $phpBin, $phpIniScanDir);

        return 0;
    }

    private function runCommand(string $command, string $phpBin, string $phpIniScanDir): int
    {
        $process = Process::fromShellCommandline($command, getcwd(), ['PHP_BIN' => $phpBin, 'PHP_INI_SCAN_DIR' => $phpIniScanDir]);
        $result = $process->setTty(true)->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $result;
    }
}
