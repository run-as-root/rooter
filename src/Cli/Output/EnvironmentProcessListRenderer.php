<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Output;

use RunAsRoot\Rooter\Api\ProcessCompose\Exception\ApiException;
use RunAsRoot\Rooter\Api\ProcessCompose\ProcessComposeApi;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

readonly class EnvironmentProcessListRenderer
{
    public function __construct(
        private ProcessComposeApi $processComposeApi
    ) {
    }

    public function render(array $envData, InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);
        try {
            $this->processComposeApi->isAlive($envData);
        } catch (\Exception $e) {
            $io->note("environment seems to be stopped." . PHP_EOL . "start environment to see process list.");
            return;
        }

        try {
            $processData = $this->processComposeApi->getProcessList($envData);
        } catch (ApiException $e) {
            $io->error($e->getMessage());
            return;
        } catch (\JsonException $e) {
            $io->error("Could not parse json. Invalid json response from process-compose: {$e->getMessage()}");
            return;
        } catch (\Exception $e) {
            $io->error("Error fetching data from process-compose: {$e->getMessage()}");
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
