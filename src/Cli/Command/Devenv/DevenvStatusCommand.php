<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Devenv;

use RunAsRoot\Rooter\Api\ProcessCompose\ProcessComposeApi;
use RunAsRoot\Rooter\Repository\EnvironmentRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DevenvStatusCommand extends Command
{
    public function __construct(
        private readonly EnvironmentRepository $environmentRepository,
        private readonly ProcessComposeApi $processComposeApi
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('devenv:status');
        $this->setDescription('show status of the devenv');
        $this->addArgument('name', InputArgument::OPTIONAL, 'The name of the env');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectName = $input->getArgument('name') ?? getenv('PROJECT_NAME');
        if (!$projectName) {
            $output->writeln("<error>Please provide a project-name or execute in a project context.</error>");
            return Command::FAILURE;
        }

        $envData = $this->environmentRepository->getByName($projectName);

        $status = $this->isEnvRunning($envData) ? 'running' : 'stopped';

        $table = new Table($output);
        $table->setStyle('box');
        $table->setHeaders(['name', 'status', 'socket']);
        $table->setRows([
            ['devenv', $status, $envData['processComposeSocket']],
        ]);
        $table->render();

        return Command::SUCCESS;
    }

    private function isEnvRunning(array $envData): bool
    {
        try {
            $this->processComposeApi->isAlive($envData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
