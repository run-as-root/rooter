<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use RunAsRoot\Rooter\Repository\EnvironmentRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RegisterEnvCommand extends Command
{
    private EnvironmentRepository $environmentRepository;

    public function configure()
    {
        $this->setName('env:register');
        $this->setDescription('Register a project');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->environmentRepository = new EnvironmentRepository();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectName = getenv('PROJECT_NAME');

        if (empty($projectName)) {
            $output->writeln("<error>PROJECT_NAME is not set. This command should be executed in a project context.</error>");
            return Command::FAILURE;
        }

        try {
            $this->environmentRepository->register($projectName);
        } catch (\Exception $e) {
            $output->writeln("<error>Failed to register environment: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }

        $output->writeln('<info>environment registered successfully.</info>');
        return Command::SUCCESS;
    }

}
