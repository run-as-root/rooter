<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Dnsmasq;

use RunAsRoot\Rooter\Config\DnsmasqConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitDnsmasqConfigCommand extends Command
{
    private DnsmasqConfig $dnsmasqConfig;

    public function configure()
    {
        $this->setName('dnsmasq:config:init');
        $this->setDescription('Initialise rooter dnsmasq configuration for user in $HOME');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->dnsmasqConfig = new DnsmasqConfig();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dnsmasqConfDir = $this->dnsmasqConfig->getHomeDir();
        if (!is_dir($dnsmasqConfDir)) {
            mkdir($dnsmasqConfDir, 0755, true);
        }

        $logDir = $this->dnsmasqConfig->getLogDir();
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $dnsmasqConf = $this->dnsmasqConfig->getDnsmasqConf();
        if (file_exists($dnsmasqConf)) {
            unlink($dnsmasqConf);
        }

        # @todo allow port adjustment?
        copy($this->dnsmasqConfig->getConfTmpl(), $dnsmasqConf);

        $resolverConfTmpl = $this->dnsmasqConfig->getResolverTmpl();
        $resolverConf = $this->dnsmasqConfig->getResolverConf();
        if (!is_file($resolverConf)) {
            exec("sudo cp $resolverConfTmpl $resolverConf");
        }

        return 0;
    }
}
