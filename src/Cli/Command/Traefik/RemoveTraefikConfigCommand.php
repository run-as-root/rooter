<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Traefik;

use RunAsRoot\Rooter\Config\TraefikConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveTraefikConfigCommand extends Command
{
    public function __construct(private readonly TraefikConfig $traefikConfig)
    {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('traefik:config:remove');
        $this->setDescription('Remove a project specific traefik config');
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

        $targetFile = $this->traefikConfig->getEndpointConfPath($projectName);

        if (!is_file($targetFile)) {
            return Command::FAILURE;
        }
        unlink($targetFile);

        $output->writeln("traefik configuration removed for $projectName");

        return Command::SUCCESS;
    }
}
