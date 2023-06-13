<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Traefik;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ShowTraefikLogCommand extends Command
{
    public function configure()
    {
        $this->setName('traefik:log');
        $this->setDescription('Show Traefik logs');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $traefikLog = ROOTER_HOME_DIR . '/traefik/logs/traefik.log';

        $command = "tail -f $traefikLog";

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(0);
        $process->setTty(true);
        $process->setOptions(['create_new_console' => 1]);
        $process->run();

        return 0;
    }
}
