<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Config;

class DevenvConfig
{
    private string $pidFile = ROOTER_PROJECT_ROOT . '/.devenv/state/devenv.pid';
    private string $logFile = ROOTER_PROJECT_ROOT . '/.devenv/state/devenv.log';

    public function getPidFile(): string
    {
        return $this->pidFile;
    }

    public function getLogFile(): string
    {
        return $this->logFile;
    }
}
