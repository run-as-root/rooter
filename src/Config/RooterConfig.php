<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Config;

class RooterConfig
{
    private string $binDir = ROOTER_HOME_DIR . '/bin';
    private string $environmentDir = ROOTER_HOME_DIR . '/environments';
    private string $environmentTemplatesDir = ROOTER_DIR . '/environments';

    public function getBinDir(): string
    {
        return $this->binDir;
    }

    public function getEnvironmentDir(): string
    {
        return $this->environmentDir;
    }

    public function getEnvironmentTemplatesDir(): string
    {
        return $this->environmentTemplatesDir;
    }

    public function getEnvironmentTypes(): array
    {
        $types = [];

        $templatesDir = $this->getEnvironmentTemplatesDir();

        $availableEnvironments = scandir($templatesDir);
        foreach ($availableEnvironments as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            if(!is_dir("$templatesDir/$name")) {
                continue;
            }
            $types[] = $name;
        }

        return $types;
    }

}
