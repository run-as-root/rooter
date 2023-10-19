<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AmqpAdminCommand extends Command
{
    public function configure()
    {
        $this->setName('amqp-admin');
        $this->setDescription('launch AMQP Web Admin UI');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $port = getenv('DEVENV_AMQP_MANAGEMENT_PORT');
        if (empty($port)) {
            $output->writeln("<error>DEVENV_AMQP_MANAGEMENT_PORT is not set.</error>");
            return Command::FAILURE;
        }

        shell_exec("open http://127.0.0.1:$port");

        return 0;
    }
}
