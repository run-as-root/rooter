<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Devenv;

use RunAsRoot\Rooter\Config\DevenvConfig;
use RunAsRoot\Rooter\Manager\ProcessManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DevenvStatusCommand extends Command
{
    public function __construct(
        private readonly ProcessManager $processManager,
        private readonly DevenvConfig $devenvConfig,
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

        $pid = $this->processManager->getPidFromFile($this->devenvConfig->getPidFile());

        $status = $this->processManager->isRunningByPid($pid) ? 'running' : 'stopped';

        $table = new Table($output);
        $table->setStyle('box');
        $table->setHeaders(['name', 'status', 'pid']);
        $table->setRows([
            ['devenv', $status, $pid],
        ]);
        $table->render();

        return Command::SUCCESS;
    }

}
