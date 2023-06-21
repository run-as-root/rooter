<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Traefik;

use RunAsRoot\Rooter\Config\TraefikConfig;
use RunAsRoot\Rooter\Manager\ProcessManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StopTraefikCommand extends Command
{
    private TraefikConfig $traefikConfig;
    private ProcessManager $processManager;

    public function configure()
    {
        $this->setName('traefik:stop');
        $this->setDescription('Stop Traefik');
        $this->setHidden();
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->traefikConfig = new TraefikConfig();
        $this->processManager = new ProcessManager();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pidFile = $this->traefikConfig->getPidFile();

        $this->processManager->stop($pidFile);
        $output->writeln("<info>traefik was stopped</info>");

        return 0;
    }
}
