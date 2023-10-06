<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use RunAsRoot\Rooter\Manager\DotEnvFileManager;
use RunAsRoot\Rooter\Manager\PortManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'env:ports:init',
    description: 'find free port for each service and write them to .env',
)]
class InitEnvPortsCommand extends Command
{
    public function __construct(
        private readonly PortManager $portManager,
        private readonly DotEnvFileManager $dotEnvFileManager
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addOption('write', 'w', InputOption::VALUE_NONE, 'write contents in .env');
        $this->addOption('print', 'p', InputOption::VALUE_NONE, 'print the added env variables');
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Finding ports ...');

        $ports = $this->portManager->findFreePortsForRanges(true);

        if ($input->getOption('write')) {
            $output->writeln('Writing ports to .env');
            $this->dotEnvFileManager->write($ports);
        }

        $output->writeln('Ports available for this environment:');
        foreach ($ports as $varName => $varValue) {
            $output->writeln("$varName=$varValue");
        }

        return Command::SUCCESS;
    }

}

