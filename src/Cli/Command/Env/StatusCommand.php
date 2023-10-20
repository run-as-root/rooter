<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use Exception;
use JsonException;
use RunAsRoot\Rooter\Api\ProcessCompose\Exception\ApiException;
use RunAsRoot\Rooter\Api\ProcessCompose\ProcessComposeApi;
use RunAsRoot\Rooter\Cli\Output\EnvironmentConfigRenderer;
use RunAsRoot\Rooter\Cli\Output\Style\TitleOutputStyle;
use RunAsRoot\Rooter\Repository\EnvironmentRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class StatusCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly EnvironmentRepository $envRepository,
        private readonly ProcessComposeApi $processComposeApi,
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('status');
        $this->setAliases(['env:status']);
        $this->setDescription('show status of env');
        $this->addArgument('name', InputArgument::OPTIONAL, 'The name of the env');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectName = $input->getArgument('name') ?? getenv('PROJECT_NAME');
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

        $this->io->block((string)$projectName, null, TitleOutputStyle::NAME, '  ', true);

        $this->renderEnvironmentProcessList($envData, $input, $output);

        $environmentConfigRenderer = new EnvironmentConfigRenderer();
        $environmentConfigRenderer->render($envData, $output);

        return Command::SUCCESS;
    }

    private function renderEnvironmentProcessList(array $envData, InputInterface $input, OutputInterface $output): void
    {
        try {
            $this->processComposeApi->isAlive($envData);
        } catch (Exception $e) {
            $this->io->note("environment seems to be stopped." . PHP_EOL . "start environment to see process list.");
            return;
        }

        try {
            $processData = $this->processComposeApi->getProcessList($envData);
        } catch (ApiException $e) {
            $this->io->error($e->getMessage());
            return;
        } catch (JsonException $e) {
            $this->io->error("Could not parse json. Invalid json response from process-compose: {$e->getMessage()}");
            return;
        } catch (Exception $e) {
            $this->io->error("Error fetching data from process-compose: {$e->getMessage()}");
            return;
        }

        $table = new Table($output);
        $table->setStyle('box');
        $table->setHeaders(['process', 'PID', 'uptime', 'health', 'exit-code', 'status']);

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
