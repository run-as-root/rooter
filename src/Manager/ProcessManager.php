<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Manager;

use RunAsRoot\Rooter\Exception\FailedToStopProcessException;
use RunAsRoot\Rooter\Exception\ProcessNotRunningException;

class ProcessManager
{
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

    public function getPidFromFile(string $pidFile): string
    {
        if (!is_file($pidFile)) {
            return "";
        }
        return trim(file_get_contents($pidFile));
    }
}
