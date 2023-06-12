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
        $this->setDescription('Run Traefik in foreground');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $traefikConf = ROOTER_HOME_DIR . '/traefik/traefik.yml';

        $command = "traefik --configfile=$traefikConf";

        return Process::fromShellCommandline($command)->setTty(true)->run();
    }
}
