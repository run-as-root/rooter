<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use RunAsRoot\Rooter\Cli\Command\Traefik\RegisterTraefikConfigCommand;
use RunAsRoot\Rooter\Repository\EnvironmentRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RegisterEnvCommand extends Command
{
    public function __construct(
        private readonly EnvironmentRepository $environmentRepository,
        private readonly RegisterTraefikConfigCommand $registerTraefik
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('env:register');
        $this->setDescription('Register a project');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectName = getenv('PROJECT_NAME');

        if (!$projectName) {
            $output->writeln("<error>PROJECT_NAME is not set. This command should be executed in a project context.</error>");
            return Command::FAILURE;
        }

        // Register Environment
        try {
            $this->environmentRepository->register($projectName);
        } catch (\Exception $e) {
            $output->writeln("<error>Failed to register environment: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }
        $output->writeln('<info>environment registered successfully.</info>');

        // Register Traefik Config
        $this->registerTraefik->run(new ArrayInput([]), $output);

        return Command::SUCCESS;
    }

}
