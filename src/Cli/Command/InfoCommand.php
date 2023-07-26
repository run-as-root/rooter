<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InfoCommand extends Command
{
    public function configure()
    {
        $this->setName('info');
        $this->setDescription('summary about environment');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (empty(getenv("PROJECT_NAME"))) {
            $output->writeln("This command should be executed in a project context. PROJECT_NAME is empty");

            return 1;
        }

        $mysql = sprintf(
            "mysql://%s:%s@127.0.0.1:%s/%s",
            getenv('DEVENV_DB_USER'), getenv('DEVENV_DB_PASS'), getenv('DEVENV_DB_PORT'), getenv('DEVENV_DB_NAME')
        );

        $table = new Table($output);
        $table->setStyle('box');
        $table->setHeaders(['PROJECT', getenv("PROJECT_NAME")]);
        $table->setRows([
            ['app', 'http://' . getenv('PROJECT_HOST')],
            ['mailhog', 'http://' . getenv('PROJECT_NAME') . '-mailhog.rooter.test'],
            ['AMQP-admin', 'http://' . getenv('PROJECT_NAME') . '-amqp.rooter.test'],
            new TableSeparator(),
            ['nginx', 'http(' . getenv('DEVENV_HTTP_PORT') . ') https(' . getenv('DEVENV_HTTPS_PORT') . ')'],
            ['DB', $mysql,],
            ['redis', 'http://127.0.0.1:' . getenv('DEVENV_REDIS_PORT')],
            ['AMQP', 'http://127.0.0.1:' . getenv('DEVENV_AMQP_PORT')],
            ['AMQP-admin', 'http://127.0.0.1:' . getenv('DEVENV_AMQP_MANAGEMENT_PORT')],
            ['elasticsearch', 'http://127.0.0.1:' . getenv('DEVENV_ELASTICSEARCH_PORT')],
            new TableSeparator(),
            ['mail UI', 'http://127.0.0.1:' . getenv('DEVENV_MAIL_UI_PORT')],
            ['mail SMTP', 'http://127.0.0.1:' . getenv('DEVENV_MAIL_SMTP_PORT')],
        ]);
        $table->render();

        return 0;
    }
}
