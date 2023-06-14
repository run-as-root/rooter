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
        $this->setHidden();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pidFile = ROOTER_HOME_DIR . '/traefik/traefik.pid';
        $traefikConf = ROOTER_HOME_DIR . '/traefik/traefik.yml';
        $TRAEFIK_BIN = ROOTER_HOME_DIR . "/bin/traefik";

        $pid = null;
        if (is_file($pidFile)) {
            $pid = file_get_contents($pidFile);
        }
        if ($pid > 0) {
            $output->writeln("traefik is already running with PID:$pid");

            return 1;
        }

        $command = "$TRAEFIK_BIN --configfile=$traefikConf";

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(0);
        $process->setOptions(['create_new_console' => 1]);

        $process->start();

        sleep(2); # we need to wait a moment here

        $pid = $process->getPid();

        file_put_contents($pidFile, $pid);

        $output->writeln("<info>traefik is running with PID:$pid</info>");

        return 0;
    }
}
