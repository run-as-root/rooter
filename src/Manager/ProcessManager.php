<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Manager;

use RunAsRoot\Rooter\Exception\FailedToStopProcessException;
use RunAsRoot\Rooter\Exception\ProcessAlreadyRunningException;
use RunAsRoot\Rooter\Exception\ProcessNotRunningException;
use Symfony\Component\Process\Process;

class ProcessManager
{
    public function start(string $command, bool $preservePhpContext = false): Process
    {
        if ($preservePhpContext) {
            $command = $this->preservePhpContext($command);
        }

        $process = Process::fromShellCommandline($command, getcwd());
        $process->setTimeout(0);
        $process->setOptions(['create_new_console' => 1]);
        $process->start();

        return $process;
    }

    /**
     * @throws ProcessAlreadyRunningException
     */
    public function startWithPid(string $command, string $pidFile): void
    {
        if ($this->isRunning($pidFile)) {
            $pid = $this->getPidFromFile($pidFile);
            throw new ProcessAlreadyRunningException("pid: $pid");
        }

        $process = Process::fromShellCommandline($command, getcwd());
        $process->setTimeout(0);
        $process->setOptions(['create_new_console' => 1]);
        $process->start();

        sleep(2); # we need to wait a moment here

        file_put_contents($pidFile, $process->getPid());
    }

    public function run(string $command, bool $preservePhpContext = false): Process
    {
        if ($preservePhpContext) {
            $command = $this->preservePhpContext($command);
        }

        $process = Process::fromShellCommandline($command, getcwd());
        $process->setTimeout(0);
        $process->setOptions(['create_new_console' => 1]);
        $process->setTty(true);
        $process->run();

        return $process;
    }

    private function preservePhpContext(string $command): string
    {
        $phpBin = exec('which php');
        $phpBin = realpath($phpBin);
        $phpIniScanDir = dirname($phpBin, 2) . "/lib";

        // ROOTER uses a specific PHP version which may not match the one from the env
        // here me make sure that the correct PHP_BIN and PHP_INI_SCAN_DIR is set
        // We need to preserve the env from the project and not use rooter env
        return "export PHP_BIN=\"$phpBin\" PHP_INI_SCAN_DIR=\"$phpIniScanDir\" && " . $command;
    }

    /**
     * @throws ProcessNotRunningException
     * @throws FailedToStopProcessException
     */
    public function stop(string $pidFile): bool
    {
        $pid = $this->getPidFromFile($pidFile);
        if ($pid <= 0) {
            throw new ProcessNotRunningException("process is not running for PID:$pid");
        }

        if ($ok = proc_open(sprintf('kill -%d %d', 2, $pid), [2 => ['pipe', 'w']], $pipes)) {
            $ok = false === fgets($pipes[2]);
        }

        if (!$ok) {
            throw new FailedToStopProcessException("Could not stop process with PID:$pid");
        }

        sleep(2);

        if (is_file($pidFile)) {
            file_put_contents($pidFile, '');
        }

        return true;
    }

    public function isRunningByPid(string $pid): bool
    {
        if (empty($pid)) {
            return false;
        }
        return posix_kill((int)$pid, 0);
    }

    public function isRunning(string $pidFile): bool
    {
        $pid = $this->getPidFromFile($pidFile);
        if (empty($pid)) {
            return false;
        }
        return posix_kill((int)$pid, 0);
    }

    public function hasPid(string $pidFile): bool
    {
        return file_exists($pidFile) && trim(file_get_contents($pidFile)) !== '';
    }

    public function getPidFromFile(string $pidFile): string
    {
        if (!is_file($pidFile)) {
            return "";
        }
        return trim(file_get_contents($pidFile));
    }
}
