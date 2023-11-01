<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Config;

readonly class DnsmasqConfig
{
    public function __construct(
        private string $dnsmasqBin,
        private string $homeDir,
        private string $pidFile,
        private string $logDir,
        private string $dnsmasqConf,
        private string $confTmpl,
        private string $resolverTmpl,
        private string $resolverConf
    ) {
    }

    public function getDnsmasqCommand(): string
    {
        return "{$this->getDnsmasqBin()} --conf-file={$this->getDnsmasqConf()} --no-daemon";
    }

    public function getDnsmasqBin(): string
    {
        return getenv('ROOTER_DNSMASQ_BIN') ?: $this->dnsmasqBin;
    }

    public function getHomeDir(): string
    {
        return $this->homeDir;
    }

    public function getPidFile(): string
    {
        return $this->pidFile;
    }

    public function getDnsmasqConf(): string
    {
        return $this->dnsmasqConf;
    }

    public function getLogDir(): string
    {
        return $this->logDir;
    }

    public function getConfTmpl(): string
    {
        return $this->confTmpl;
    }

    public function getResolverTmpl(): string
    {
        return $this->resolverTmpl;
    }

    public function getResolverConf(): string
    {
        return $this->resolverConf;
    }

}
