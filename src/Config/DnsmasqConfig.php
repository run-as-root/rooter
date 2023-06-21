<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Config;

class DnsmasqConfig
{
    private string $dnsmasqkBin = ROOTER_HOME_DIR . "/bin/dnsmasq";
    private string $homeDir = ROOTER_HOME_DIR . '/dnsmasq';
    private string $pidFile = ROOTER_HOME_DIR . '/dnsmasq/dnsmasq.pid';
    private string $logDir = ROOTER_HOME_DIR . '/dnsmasq/logs';
    private string $dnsmasqConf = ROOTER_HOME_DIR . '/dnsmasq/dnsmasq.conf';
    private string $resolverConf = '/etc/resolver/rooter.test';
    private string $confTmpl = ROOTER_DIR . '/etc/dnsmasq/dnsmasq.conf';
    private string $resolverTmpl = ROOTER_DIR . '/etc/resolver/rooter.test';

    public function getDnsmasqCommand(): string
    {
        $DNSMASQ_BIN = $this->getDnsmasqBin();
        $dnsmasqConf = $this->getDnsmasqConf();
        return "$DNSMASQ_BIN --conf-file=$dnsmasqConf --no-daemon";
    }

    public function getDnsmasqBin(): string
    {
        return $this->dnsmasqkBin;
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

    public function getConfTmpl(): string
    {
        return $this->confTmpl;
    }

    public function getLogDir(): string
    {
        return $this->logDir;
    }

    public function getResolverConf(): string
    {
        return $this->resolverConf;
    }

    public function getResolverTmpl(): string
    {
        return $this->resolverTmpl;
    }

}
