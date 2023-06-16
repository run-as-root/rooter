<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Traefik;

use RunAsRoot\Rooter\Config\TraefikConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class StartTraefikCommand extends Command
{
    private TraefikConfig $traefikConfig;

    public function configure()
    {
        $this->setName('traefik:start');
        $this->setDescription('Run Traefik in background');
        $this->setHidden();
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->traefikConfig = new TraefikConfig();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pidFile = $this->traefikConfig->getPidFile();

        $pid = null;
        if (is_file($pidFile)) {
            $pid = file_get_contents($pidFile);
        }
        if ($pid > 0) {
            $output->writeln("traefik is already running with PID:$pid");

            return 1;
        }

        $traefikConf = $this->traefikConfig->getTraefikConf();
        $TRAEFIK_BIN = $this->traefikConfig->getTraefikBin();

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
