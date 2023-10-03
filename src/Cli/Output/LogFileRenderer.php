<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Output;

use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Reads the log file and outputs it to the console as long as new content is written to the file
 * If no new content is written for X seconds, the output is stopped
 */
class LogFileRenderer
{
    private int $sleepSeconds = 1;
    private int $unchangedFileChecksMax = 3;

    /**
     * @throws RuntimeException
     */
    public function render($logFilePath, OutputInterface $output): void
    {
        $file = fopen($logFilePath, 'rb');
        if (!$file) {
            throw new RuntimeException("File $logFilePath does not exist");
        }

        $lastPosition = $unchangedCounter = 0;

        fseek($file, $lastPosition);

        while (true) {
            clearstatcache();
            $currentSize = filesize($logFilePath);

            if ($currentSize > $lastPosition) {
                $file = fopen($logFilePath, 'rb');
                if (!$file) {
                    $output->writeln('<error>Unable to open the log file.</error>');
                    break;
                }

                fseek($file, $lastPosition);

                while (!feof($file)) {
                    $line = fgets($file);
                    if ($line === false || str_contains($line, 'declare -x')) {
                        continue;
                    }
                    $output->write($line);
                }

                $lastPosition = ftell($file);

                fclose($file);
            } else {
                $unchangedCounter++;
            }

            sleep($this->sleepSeconds);

            if ($unchangedCounter > $this->unchangedFileChecksMax) {
                break;
            }
        }
    }
}
