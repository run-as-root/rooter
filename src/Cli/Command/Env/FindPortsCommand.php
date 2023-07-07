<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use RunAsRoot\Rooter\Manager\PortManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FindPortsCommand extends Command
{
    private PortManager $portManager;

    protected function configure()
    {
        $this->setName('env:ports:find');
        $this->setDescription('find available ports for environment');
        $this->addOption('count', 'c', InputOption::VALUE_REQUIRED, 'the number of ports you want');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->portManager = new PortManager();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Scanning for free ports …');

        $max = $input->getOption('count') ?? 5;

        for ($i = 0; $i < $max; $i++) {
            $freePort = $this->portManager->findFreePort();

            $output->writeln("Free port: $freePort");
        }

        return Command::SUCCESS;
    }
}

