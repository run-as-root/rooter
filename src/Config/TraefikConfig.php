<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Config;

readonly class TraefikConfig
{
    public function __construct(
        private string $traefikBin,
        private string $traefikHomeDir,
        private string $pidFile,
        private string $traefikConf,
        private string $traefikLog,
        private string $confDir,
        private string $logDir,
        private string $confTmpl,
        private string $endpointDefault,
        private string $endpointTmpl
    ) {
    }

    public function getTraefikCommand(): string
    {
        return "{$this->getTraefikBin()} --configfile={$this->getTraefikConf()}";
    }

    public function getTraefikBin(): string
    {
        return getenv('ROOTER_TRAEFIK_BIN') ?: $this->traefikBin;
    }

    public function getTraefikHomeDir(): string
    {
        return $this->traefikHomeDir;
    }

    public function getPidFile(): string
    {
        return $this->pidFile;
    }

    public function getTraefikConf(): string
    {
        return $this->traefikConf;
    }

    public function getTraefikLog(): string
    {
        return $this->traefikLog;
    }

    public function getConfDir(): string
    {
        return $this->confDir;
    }

    public function getEndpointConfPath(string $envName): string
    {
        return $this->getConfDir() . "/$envName.yml";
    }

    public function getLogDir(): string
    {
        return $this->logDir;
    }

    public function getEndpointTmpl(): string
    {
        return $this->endpointTmpl;
    }

    public function getConfTmpl(): string
    {
        return $this->confTmpl;
    }

    public function getEndpointDefault(): string
    {
        return $this->endpointDefault;
    }
}
