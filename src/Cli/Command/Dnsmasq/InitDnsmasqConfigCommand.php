<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Dnsmasq;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitDnsmasqConfigCommand extends Command
{
    public function configure()
    {
        $this->setName('dnsmasq:config:init');
        $this->setDescription('Initialise rooter dnsmasq configuration for user in $HOME');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dnsmasqConfDir = ROOTER_HOME_DIR . '/dnsmasq';
        $dnsmasqConf = $dnsmasqConfDir . '/dnsmasq.conf';

        if (!is_dir($dnsmasqConfDir)) {
            mkdir($dnsmasqConfDir, 0755, true);
        }
        if (!is_dir("$dnsmasqConfDir/logs/")) {
            mkdir("$dnsmasqConfDir/logs/", 0755, true);
        }

        if (file_exists($dnsmasqConf)) {
            unlink($dnsmasqConf);
        }

        # @todo allow port adjustment
        copy(ROOTER_DIR . '/etc/dnsmasq/dnsmasq.conf', $dnsmasqConf);

        $resolverConfTmpl = ROOTER_DIR . '/etc/resolver/rooter.test';
        $resolverConf = '/etc/resolver/rooter.test';
        if (!is_file($resolverConf)) {
            # @todo allow port adjustment
            exec("sudo cp $resolverConfTmpl $resolverConf");
        }

        return 0;
    }
}
