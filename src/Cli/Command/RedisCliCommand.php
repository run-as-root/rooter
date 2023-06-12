<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class RedisCliCommand extends Command
{
    public function configure()
    {
        $this->setName('redis-cli');
        $this->setDescription('Run redis-cli inside the redis container');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $port = getenv('DEVENV_REDIS_PORT');

        $command = "redis-cli -p $port -h 127.0.0.1";

        return Process::fromShellCommandline($command)->setTty(true)->run();
    }
}
