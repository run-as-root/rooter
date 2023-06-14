<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Traefik;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OpenTraefikDashboardCommand extends Command
{
    public function configure()
    {
        $this->setName('traefik:dashboard');
        $this->setDescription('Open Traefik Dashboard');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pid = file_get_contents(ROOTER_HOME_DIR . '/traefik/traefik.pid');
        if ($pid <= 0) {
            $output->writeln("traefik is stopped, you need to start if first");

            return 1;
        }

        shell_exec("open 'http://127.0.0.1:8080/dashboard/'");

        return 0;
    }
}
