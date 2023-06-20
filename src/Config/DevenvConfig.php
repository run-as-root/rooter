<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Config;

class DevenvConfig
{
    private const DEVENV_STATE_DEVENV_PID = '%s/.devenv/state/devenv.pid';
    private const DEVENV_STATE_DEVENV_LOG = '%s/.devenv/state/devenv.log';

    public function getPidFile(string $path = null): string
    {
        $path = $path ?? ROOTER_PROJECT_ROOT;
        return sprintf(self::DEVENV_STATE_DEVENV_PID, $path);
    }

    public function getLogFile(string $path = null): string
    {
        $path = $path ?? ROOTER_PROJECT_ROOT;
        return sprintf(self::DEVENV_STATE_DEVENV_LOG, $path);
    }
}
