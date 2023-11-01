<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use RunAsRoot\Rooter\Cli\Output\EnvironmentListRenderer;
use RunAsRoot\Rooter\Repository\EnvironmentRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends Command
{
    public function __construct(
        private readonly EnvironmentRepository $envRepository,
        private readonly EnvironmentListRenderer $environmentListRenderer,
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('list');
        $this->setAliases(['ps']);
        $this->setDescription('list all projects');
        $this->addOption('ports', '', InputOption::VALUE_NONE, 'show all ports');
        $this->addOption('running', '', InputOption::VALUE_NONE, 'filter for running environments');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->environmentListRenderer->render(
            environments: $this->envRepository->getList(),
            input: $input,
            output: $output,
            onlyRunning: $input->getOption('running'),
            showPorts: $input->getOption('ports'),
        );

        return Command::SUCCESS;
    }

}
