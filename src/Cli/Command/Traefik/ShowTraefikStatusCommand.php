<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Traefik;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowTraefikStatusCommand extends Command
{
    public function configure()
    {
        $this->setName('traefik:status');
        $this->setDescription('Show Traefik status');
        $this->setHidden();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pid = file_get_contents(ROOTER_HOME_DIR . '/traefik/traefik.pid');
        if ($pid >= 0) {
            $output->writeln("traefik is running with PID:$pid");
        } else {
            $output->writeln("traefik is stopped");
        }

        return 0;
    }
}
