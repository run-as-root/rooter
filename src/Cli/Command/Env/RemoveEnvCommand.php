<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use RunAsRoot\Rooter\Repository\EnvironmentRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveEnvCommand extends Command
{
    public function __construct(private readonly EnvironmentRepository $environmentRepository)
    {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('env:remove');
        $this->setDescription('Remove a registered environment');
        $this->addArgument('name', InputArgument::OPTIONAL, 'the name of the environment');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectName = $input->getArgument('name');
        $projectName = $projectName ?? getenv('PROJECT_NAME');

        if (empty($projectName)) {
            $output->writeln("<error>PROJECT_NAME is not set. This command should be executed in a project context.</error>");
            return Command::FAILURE;
        }

        try {
            $this->environmentRepository->delete($projectName);
        } catch (\Exception $e) {
            $output->writeln("<error>Failed to remove environment: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }
        $output->writeln('<info>environment removed successfully.</info>');

        return Command::SUCCESS;
    }

}
