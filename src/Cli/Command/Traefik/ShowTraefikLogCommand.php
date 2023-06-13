<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Traefik;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ShowTraefikLogCommand extends Command
{
    public function configure()
    {
        $this->setName('traefik:log');
        $this->setDescription('Show Traefik logs');
        $this->addOption('follow', 'f', InputOption::VALUE_NONE, 'follow the log output');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $traefikLog = ROOTER_HOME_DIR . '/traefik/logs/traefik.log';

        $follow = $input->getOption('follow') ? '-f' : '';

        $command = "tail $follow $traefikLog";

        Process::fromShellCommandline($command)->setTimeout(0)->setTty(true)->run();

        return 0;
    }
}
