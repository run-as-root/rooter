<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Traefik;

use RunAsRoot\Rooter\Config\TraefikConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ShowTraefikLogCommand extends Command
{
    private TraefikConfig $traefikConfig;

    public function configure()
    {
        $this->setName('traefik:log');
        $this->setDescription('Show Traefik logs');
        $this->addOption('follow', 'f', InputOption::VALUE_NONE, 'follow the log output');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->traefikConfig = new TraefikConfig();
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $traefikLog = $this->traefikConfig->getTraefikLog();

        $follow = $input->getOption('follow') ? '-f' : '';

        $command = "tail $follow $traefikLog";

        Process::fromShellCommandline($command)->setTimeout(0)->setTty(true)->run();

        return 0;
    }
}
