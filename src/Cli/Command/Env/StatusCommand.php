<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use RunAsRoot\Rooter\Api\ProcessCompose\Exception\ApiException;
use RunAsRoot\Rooter\Api\ProcessCompose\ProcessComposeApi;
use RunAsRoot\Rooter\Config\DevenvConfig;
use RunAsRoot\Rooter\Manager\ProcessManager;
use RunAsRoot\Rooter\Repository\EnvironmentRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StatusCommand extends Command
{
    public function __construct(
        private readonly ProcessManager $processManager,
        private readonly DevenvConfig $devenvConfig,
        private readonly EnvironmentRepository $envRepository,
        private readonly ProcessComposeApi $processComposeApi,
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('env:status');
        $this->setDescription('show status of env');
        $this->addArgument('name', InputArgument::OPTIONAL, 'The name of the env');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectName = $input->getArgument('name') ?? getenv('PROJECT_NAME');
        if (!$projectName) {
            $output->writeln("<error>Please provide a project-name or execute in a project context.</error>");
            return Command::FAILURE;
        }

        try {
            $envData = $this->envRepository->getByName($projectName);
        } catch (\JsonException $e) {
            $output->writeln("<error>Could not get environment config: invalid json {$e->getMessage()}</error>");
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

        $this->renderEnvironmentProcessList($envData, $output);

        return Command::SUCCESS;
    }

    private function renderEnvironmentProcessList(array $envData, OutputInterface $output): void
    {
        try {
            $this->processComposeApi->isAlive($envData);

            $processData = $this->processComposeApi->getProcessList($envData);
        } catch (ApiException $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return;
        } catch (\JsonException $e) {
            $output->writeln("<error>Could not parse json. Invalid json response from process-compose: {$e->getMessage()}</error>");
            return;
        } catch (\Exception $e) {
            $output->writeln("<error>Error fetching data from process-compose: {$e->getMessage()}</error>");
            return;
        }

        $table = new Table($output);
        $table->setStyle('box');
        $table->setHeaderTitle('Process Overview');
        $table->setHeaders(['Name', 'PID', 'uptime', 'Health', 'ExitCode', 'Status']);

        foreach ($processData as $process) {
            $table->addRow(
                [
                    $process['name'],
                    $process['pid'],
                    $process['system_time'],
                    $process['is_ready'],
                    $process['exit_code'],
                    $process['status'],
                ]
            );
        }

        $table->render();
    }
}
