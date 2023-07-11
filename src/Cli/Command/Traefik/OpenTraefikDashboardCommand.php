<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Traefik;

use RunAsRoot\Rooter\Config\TraefikConfig;
use RunAsRoot\Rooter\Manager\ProcessManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OpenTraefikDashboardCommand extends Command
{
    public function __construct(
        private readonly ProcessManager $processManager,
        private readonly TraefikConfig $traefikConfig
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('traefik:dashboard');
        $this->setDescription('Open Traefik Dashboard');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $traefikPid = $this->processManager->getPidFromFile($this->traefikConfig->getPidFile());
        if (!($this->processManager->isRunningByPid($traefikPid))) {
            $output->writeln("traefik is stopped, you need to start if first");

            return 1;
        }

        shell_exec("open 'http://127.0.0.1:8080/dashboard/'");

        return 0;
    }
}
