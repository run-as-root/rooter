<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Traefik;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class StartTraefikCommand extends Command
{
    public function configure()
    {
        $this->setName('traefik:start');
        $this->setDescription('Run Traefik in background');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pid = file_get_contents(ROOTER_HOME_DIR . '/traefik/traefik.pid');
        if ($pid >= 0) {
            $output->writeln("<error>traefik is already running with PID:$pid</error>");

            return 1;
        }

        $traefikConf = ROOTER_HOME_DIR . '/traefik/traefik.yml';

        $command = "traefik --configfile=$traefikConf";

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(0);
        $process->setOptions(['create_new_console' => 1]);

        $process->start();

        sleep(2); # we need to wait a moment here

        $pid = $process->getPid();

        file_put_contents(ROOTER_HOME_DIR . '/traefik/traefik.pid', $pid);

        return 0;
    }
}
