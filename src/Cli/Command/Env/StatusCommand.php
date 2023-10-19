<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use RunAsRoot\Rooter\Api\ProcessCompose\Exception\ApiException;
use RunAsRoot\Rooter\Api\ProcessCompose\ProcessComposeApi;
use RunAsRoot\Rooter\Cli\Output\EnvironmentConfigRenderer;
use RunAsRoot\Rooter\Config\DevenvConfig;
use RunAsRoot\Rooter\Manager\ProcessManager;
use RunAsRoot\Rooter\Repository\EnvironmentRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class StatusCommand extends Command
{
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

        $styleName = $projectName;
        $output->getFormatter()->setStyle($styleName, new OutputFormatterStyle('white', 'blue', ['bold', 'blink']));
        $output->writeln('');
        $output->writeln($this->getHelper('formatter')->formatBlock(sprintf("%s", $projectName), $styleName, true));

        $this->renderEnvironmentProcessList($envData, $input, $output);

        $environmentConfigRenderer = new EnvironmentConfigRenderer();
        $environmentConfigRenderer->render($envData, $output);

        return Command::SUCCESS;
    }

    private function renderEnvironmentProcessList(array $envData, InputInterface $input, OutputInterface $output): void
    {
        try {
            $this->processComposeApi->isAlive($envData);
        } catch (\Exception $e) {
            $io = new SymfonyStyle($input, $output);
            $io->note("environment seems to be stopped." . PHP_EOL . "start environment to see process list.");
            return;
        }

        try {
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
