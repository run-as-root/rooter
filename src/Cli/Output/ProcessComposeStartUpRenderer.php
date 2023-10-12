<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Output;

use RunAsRoot\Rooter\Api\ProcessCompose\ProcessComposeApi;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessComposeStartUpRenderer
{
    private const CLI_LINE_WIDTH = 72;
    
    public function __construct(private readonly ProcessComposeApi $processComposeApi)
    {
    }

    public function render(array $envData, OutputInterface $output): bool
    {

        $linebreakCounter = 0;
        /** @var ConsoleSectionOutput $sectionWaiting */
        $sectionWaiting = $output->section();
        $sectionWaiting->write("process-compose starting ");
        while ($this->isProcessComposeAlive($envData) === false) {
            ++$linebreakCounter % self::CLI_LINE_WIDTH === 0 ? $sectionWaiting->writeln(".") : $sectionWaiting->write(".");
            usleep(500000);
        }

        /** @var ConsoleSectionOutput $section */
        $section = $output->section();
        $isReady = false;
        $sleepMicroSeconds = 100000;
        $hasExitedProcess = false;
        $watchTime = 0;
        $watchTimeLimit = 10 * $sleepMicroSeconds;
        while ($isReady === false) {
            $processList = $this->processComposeApi->getProcessList($envData);

            $allRunning = true;
            $hasExitedProcess = false;
            $message = '';
            foreach ($processList as $process) {
                $isRunning = (bool)$process['IsRunning'];
                $exitCode = $process['exit_code'];
                $exitCodeMsg = '';
                if ($exitCode > 0) {
                    $isRunning = false;
                    $hasExitedProcess = true;
                    $exitCodeMsg = " (exit-code: $exitCode)";
                }
                if ($process['status'] === 'Completed') {
                    $isRunning = false;
                }
                $message .= sprintf("process %s is %s (pid: %s)%s\n", $process['name'], $process['status'], $process['pid'], $exitCodeMsg);

                if ($process['name'] === 'rabbitmq') { // RabbitMQ is not always detected running correctly
                    continue;
                }
                $allRunning = $allRunning && $isRunning;
            }
            $section->overwrite($message);

            usleep($sleepMicroSeconds);
            $watchTime += $sleepMicroSeconds;

            if ($watchTime > $watchTimeLimit) {
                if ($hasExitedProcess) {
                    break;
                }
                if ($allRunning === true) {
                    $isReady = true;
                }
            }
        }

        return !$hasExitedProcess;
    }

    private function isProcessComposeAlive(array $envData): bool
    {
        try {
            $this->processComposeApi->isAlive($envData);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
