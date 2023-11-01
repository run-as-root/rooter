<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Config;

class RooterConfig
{
    private string $binDir = 'bin';
    private string $envFile = '.env';
    private string $environmentsDir = 'environments';
    private string $environmentTemplatesDir = 'environments';

    public function __construct(
        private readonly string $rooterDir,
        private readonly string $rooterHomeDir,
        private readonly string $rooterSslDir,
        private readonly string $environmentRootDir
    ) {
    }

    public function getRooterDir(): string
    {
        return $this->rooterDir;
    }

    public function getRooterHomeDir(): string
    {
        return $this->rooterHomeDir;
    }

    public function getRooterSslDir(): string
    {
        return $this->rooterSslDir;
    }

    /** @deprecated */
    public function getBinDir(): string
    {
        return $this->rooterHomeDir . '/' . $this->binDir;
    }

    public function getEnvironmentRootDir(): string
    {
        return $this->environmentRootDir;
    }

    public function getEnvironmentEnvFile(): string
    {
        return $this->environmentRootDir . '/' . $this->envFile;
    }

    /** get the Path to environment storage */
    public function getEnvironmentsDir(): string
    {
        return $this->rooterHomeDir . '/' . $this->environmentsDir;
    }

    public function getEnvironmentTemplatesDir(): string
    {
        return $this->rooterDir . '/' . $this->environmentTemplatesDir;
    }

    public function getEnvironmentTemplatesDirForType(string $type): string
    {
        return "{$this->getEnvironmentTemplatesDir()}/$type";
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
            if (!is_dir("$templatesDir/$name")) {
                continue;
            }
            $types[] = $name;
        }

        return $types;
    }

    public function getPvBin(): string
    {
        return getenv('ROOTER_PV_BIN') ?: "{$this->getBinDir()}/pv";
    }

    public function getGzipBin(): string
    {
        return getenv('ROOTER_GZIP_BIN') ?: "{$this->getBinDir()}/gzip";
    }

}
