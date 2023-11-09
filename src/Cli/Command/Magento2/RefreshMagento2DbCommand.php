<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Magento2;

use RunAsRoot\Rooter\Manager\ProcessManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshMagento2DbCommand extends Command
{
    public function __construct(private readonly ProcessManager $processManager)
    {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('magento2:db-refresh');
        $this->setDescription('Refresh database from dump');
        $this->addArgument('dump-file', InputArgument::REQUIRED, 'path to db dump');
        $this->addOption('config-data-import', '', InputOption::VALUE_NONE, 'import config data after installation');
        $this->addOption('skip-reindex', '', InputOption::VALUE_NONE, 'skip reindex after importing the dump');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dbDumpFile = $input->getArgument('dump-file');
        $importConfigData = $input->getOption('config-data-import');
        $skipReindex = $input->getOption('skip-reindex');

        $MAGERUN2_BIN = getenv('MAGERUN2_BIN') ?: 'magerun2';
        $DEVENV_DB_USER = getenv('DEVENV_DB_USER');
        $DEVENV_DB_PASS = getenv('DEVENV_DB_PASS');
        $DEVENV_DB_PORT = getenv('DEVENV_DB_PORT');
        $DEVENV_DB_NAME = getenv('DEVENV_DB_NAME');

        $mysqlParams = "-u$DEVENV_DB_USER -p$DEVENV_DB_PASS --host=localhost --port=$DEVENV_DB_PORT";

        $output->writeln('Dropping and recreating database');
        exec("mysql $mysqlParams -e \"DROP DATABASE IF EXISTS $DEVENV_DB_NAME; CREATE DATABASE IF NOT EXISTS $DEVENV_DB_NAME;\"");

        $output->writeln("Importing database dump $dbDumpFile to $DEVENV_DB_NAME");
        exec("mysql $mysqlParams --database=$DEVENV_DB_NAME < $dbDumpFile");

        $output->writeln('Running magento setup:upgrade');
        $this->processManager->run("bin/magento setup:upgrade", true);

        if ($importConfigData) {
            $output->writeln('Importing config data from files with config:data:import');
            // @todo make configurable were data is fetched from, arg or env?
            $this->processManager->run("bin/magento config:data:import config/store dev/rooter", true);
        }

        $output->writeln('Remove existing admin user');
        $this->processManager->run("$MAGERUN2_BIN admin:user:delete -f -n admin", true);

        $output->writeln('Create default admin user');
        $this->processManager->run(
            "$MAGERUN2_BIN admin:user:create --admin-user=admin --admin-password=admin123 --admin-email=admin@run-as-root.sh --admin-firstname=Admin --admin-lastname=Admin",
            true
        );

        // Reindex so Elasticsearch gets the updated data
        if (!$skipReindex) {
            $output->writeln('Running magento indexer:reindex');
            $this->processManager->run("bin/magento indexer:reindex", true);
        }

        return Command::SUCCESS;
    }

}
