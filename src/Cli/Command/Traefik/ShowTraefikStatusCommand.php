<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Traefik;

use RunAsRoot\Rooter\Config\TraefikConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowTraefikStatusCommand extends Command
{
    private TraefikConfig $traefikConfig;

    public function configure()
    {
        $this->setName('traefik:status');
        $this->setDescription('Show Traefik status');
        $this->setHidden();
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->traefikConfig = new TraefikConfig();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pid = file_get_contents($this->traefikConfig->getPidFile());
        if ($pid >= 0) {
            $output->writeln("traefik is running with PID:$pid");
        } else {
            $output->writeln("traefik is stopped");
        }

        return 0;
    }
}
