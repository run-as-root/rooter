<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use JsonException;
use RunAsRoot\Rooter\Cli\Command\Services\StatusServicesCommand;
use RunAsRoot\Rooter\Cli\Output\EnvironmentConfigRenderer;
use RunAsRoot\Rooter\Cli\Output\EnvironmentListRenderer;
use RunAsRoot\Rooter\Cli\Output\EnvironmentProcessListRenderer;
use RunAsRoot\Rooter\Cli\Output\Style\TitleOutputStyle;
use RunAsRoot\Rooter\Repository\EnvironmentRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class StatusCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly EnvironmentRepository $envRepository,
        private readonly StatusServicesCommand $statusServicesCommand,
        private readonly EnvironmentProcessListRenderer $environmentProcessListRenderer,
        private readonly EnvironmentListRenderer $environmentListRenderer,
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('status');
        $this->setAliases(['env:status']);
        $this->setDescription('show status of env');
        $this->addArgument('name', InputArgument::OPTIONAL, 'The name of the env');
        $this->addOption('all', '', InputOption::VALUE_NONE, 'show status for all environments');
        $this->addOption('ports', '', InputOption::VALUE_NONE, 'show all: with ports');
        $this->addOption('running', '', InputOption::VALUE_NONE, 'show all: filter for running environments');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->statusServicesCommand->run(new ArrayInput([]), $output);

        if ($input->getOption('all')) {
            $this->environmentListRenderer->render(
                environments: $this->envRepository->getList(),
                input: $input,
                output: $output,
                onlyRunning: $input->getOption('running'),
                showPorts: $input->getOption('ports'),
            );
            $result = Command::SUCCESS;
        } else {
            $projectName = $input->getArgument('name') ?? getenv('PROJECT_NAME');
            $result = $this->renderEnvironment($projectName, $input, $output);
        }
        return $result;
    }

    private function renderEnvironment(string $projectName, InputInterface $input, OutputInterface $output): int
    {
        if (!$projectName) {
            $this->io->error("Please provide a project-name or execute in a project context.");
            return Command::FAILURE;
        }

        try {
            $envData = $this->envRepository->getByName($projectName);
        } catch (JsonException $e) {
            $this->io->error("Could not get environment config: invalid json {$e->getMessage()}");
            return Command::FAILURE;
        }

        $this->io->block(messages: $projectName, style: TitleOutputStyle::NAME, prefix: '  ', padding: true);

        $this->environmentProcessListRenderer->render($envData, $input, $output);

        $environmentConfigRenderer = new EnvironmentConfigRenderer();
        $environmentConfigRenderer->render($envData, $output);

        return Command::SUCCESS;
    }
}
