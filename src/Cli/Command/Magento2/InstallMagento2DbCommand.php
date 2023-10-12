<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Magento2;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class InstallMagento2DbCommand extends Command
{
    private string $phpBin;
    private string $phpIniScanDir;

    public function configure()
    {
        $this->setName('magento2:db-install');
        $this->setDescription('Initialise fresh database');
        $this->addOption('config-data-import', '', InputOption::VALUE_NONE, 'import config data after installation');
        $this->addOption('skip-reindex', '', InputOption::VALUE_NONE, 'skip reindex after importing the dump');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $phpBin = exec('which php');
        $this->phpBin = realpath($phpBin);
        $this->phpIniScanDir = dirname($this->phpBin, 2) . "/lib";
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $importConfigData = $input->getOption('config-data-import');
        $skipReindex = $input->getOption('skip-reindex');

        $DEVENV_DB_USER = getenv('DEVENV_DB_USER');
        $DEVENV_DB_PASS = getenv('DEVENV_DB_PASS');
        $DEVENV_DB_PORT = getenv('DEVENV_DB_PORT');
        $DEVENV_DB_NAME = getenv('DEVENV_DB_NAME');
        $PROJECT_HOST = getenv('PROJECT_HOST');
        $DEVENV_REDIS_PORT = getenv('DEVENV_REDIS_PORT');
        $DEVENV_ELASTICSEARCH_PORT = getenv('DEVENV_ELASTICSEARCH_PORT');
        $DEVENV_OPENSEARCH_PORT = getenv('DEVENV_OPENSEARCH_PORT');
        $DEVENV_AMQP_PORT = getenv('DEVENV_AMQP_PORT');
        $DEVENV_AMQP_USER = getenv('DEVENV_AMQP_USER');
        $DEVENV_AMQP_PASS = getenv('DEVENV_AMQP_PASS');

        # Drop and recreate database
        $output->writeln('Dropping and recreating database');
        $mysqlParams = "-u{$DEVENV_DB_USER} -p{$DEVENV_DB_PASS} --host=localhost --port={$DEVENV_DB_PORT}";
        exec("mysql $mysqlParams -e \"DROP DATABASE IF EXISTS $DEVENV_DB_NAME; CREATE DATABASE IF NOT EXISTS $DEVENV_DB_NAME;\"");

        # remove settings
        $output->writeln('Removing app/etc/env.php');
        $this->runCommand("rm app/etc/env.php");

        ## Magento setup:install
        $output->writeln('Running magento setup:install');
        $command = "$this->phpBin bin/magento setup:install \
                --db-host=127.0.0.1:$DEVENV_DB_PORT --db-name=$DEVENV_DB_NAME --db-user=$DEVENV_DB_USER --db-password=$DEVENV_DB_PASS \
                --admin-email=admin@run-as-root.sh --admin-firstname=Admin --admin-lastname=Admin --admin-password=admin123 --admin-user=admin \
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
                --page-cache-redis-port=$DEVENV_REDIS_PORT  ";

        if ($DEVENV_AMQP_PORT) {
            $command .= " \
                --amqp-host=127.0.0.1 --amqp-port=$DEVENV_AMQP_PORT --amqp-user=$DEVENV_AMQP_USER --amqp-password=$DEVENV_AMQP_PASS --amqp-virtualhost=\"/\"";
        }
        if ($DEVENV_ELASTICSEARCH_PORT) {
            $command .= " \
                --search-engine=elasticsearch7 \
                --elasticsearch-host=\"127.0.0.1\" --elasticsearch-port=\"$DEVENV_ELASTICSEARCH_PORT\" --elasticsearch-username=\"\" --elasticsearch-password=\"\"";
        } elseif ($DEVENV_OPENSEARCH_PORT) {
            $command .= " \
                --search-engine=opensearch \
                --opensearch-host=\"127.0.0.1\" --opensearch-port=\"$DEVENV_OPENSEARCH_PORT\" --opensearch-username=\"\" --opensearch-password=\"\"";
        }

        $this->runCommand($command);

        # Magento setup:upgrade
        $output->writeln('Running magento setup:upgrade');
        $command = "$this->phpBin bin/magento setup:upgrade";
        $this->runCommand($command);

        if($importConfigData) {
            $output->writeln('Importing config data from files with config:data:import');
            // @todo make configurable were data is fetched from, arg or env?
            $command = "$this->phpBin bin/magento config:data:import config/store dev/rooter";
            $this->runCommand($command);
        }

        // Reindex so Elasticsearch gets the updated data
        if (!$skipReindex) {
            $output->writeln('Running magento indexer:reindex');
            $command = "$this->phpBin bin/magento indexer:reindex";
            $this->runCommand($command);
        }

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
