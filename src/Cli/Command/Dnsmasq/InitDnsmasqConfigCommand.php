<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Dnsmasq;

use RunAsRoot\Rooter\Config\DnsmasqConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitDnsmasqConfigCommand extends Command
{
    public function __construct(private readonly DnsmasqConfig $dnsmasqConfig)
    {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('dnsmasq:config:init');
        $this->setDescription('Initialise rooter dnsmasq configuration for user in $HOME');
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

        // Initialise resolver config for .test and rooter.test
        $this->initialiseResolverConf($output);

        return 0;
    }

    private function initialiseResolverConf(OutputInterface $output): void
    {
        $resolverConfTmpl = $this->dnsmasqConfig->getResolverTmpl();
        $resolverConf = $this->dnsmasqConfig->getResolverConf();

        $osType = php_uname('s');
        // Configure resolver for .test domains on macOS
        if (!str_starts_with($osType, 'Darwin')) {
            $output->writeln('<comment>Manual configuration required for Automatic DNS resolution</comment>');
            return;
        }

        if (!is_dir('/etc/resolver')) {
            $output->writeln('==> Configuring resolver (requires sudo privileges)');
            exec('sudo mkdir /etc/resolver');
        }

        if (!file_exists($resolverConf)) {
            $output->writeln('==> Configuring resolver for rooter.test domains (requires sudo privileges)');
            exec("sudo cp $resolverConfTmpl $resolverConf");
        }

        $resolverConf = '/etc/resolver/test';
        if (!file_exists($resolverConf)) {
            $output->writeln('==> Configuring resolver for .test domains (requires sudo privileges)');
            exec("sudo cp $resolverConfTmpl $resolverConf");
        }
    }

}
