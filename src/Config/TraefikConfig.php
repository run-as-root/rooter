<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Config;

class TraefikConfig
{
    private string $traefikBin = ROOTER_HOME_DIR . "/bin/traefik";
    private string $traefikHomeDir = ROOTER_HOME_DIR . '/traefik';
    private string $pidFile = ROOTER_HOME_DIR . '/traefik/traefik.pid';
    private string $traefikConf = ROOTER_HOME_DIR . '/traefik/traefik.yml';
    private string $traefikLog = ROOTER_HOME_DIR . '/traefik/logs/traefik.log';
    private string $confDir = ROOTER_HOME_DIR . '/traefik/conf.d';
    private string $logDir = ROOTER_HOME_DIR . '/traefik/logs';
    private string $confTmpl = ROOTER_DIR . '/etc/traefik/traefik.yml';
    private string $endpointTmpl = ROOTER_DIR . '/etc/traefik/conf.d/endpoint-tmpl.yml';
    private string $endpointDefault = ROOTER_DIR . '/etc/traefik/conf.d/default.yml';

    public function getTraefikCommand(): string
    {
        $traefikConf = $this->getTraefikConf();
        $TRAEFIK_BIN = $this->getTraefikBin();

        return "$TRAEFIK_BIN --configfile=$traefikConf";
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

    public function getEndpointTmpl(): string
    {
        return $this->endpointTmpl;
    }

    public function getLogDir(): string
    {
        return $this->logDir;
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
