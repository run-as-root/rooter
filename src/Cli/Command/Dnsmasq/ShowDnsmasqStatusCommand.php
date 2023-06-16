<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Dnsmasq;

use RunAsRoot\Rooter\Config\DnsmasqConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowDnsmasqStatusCommand extends Command
{
    private DnsmasqConfig $dnsmasqConfig;

    public function configure()
    {
        $this->setName('dnsmasq:status');
        $this->setDescription('Show dnsmasq status');
        $this->setHidden();
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->dnsmasqConfig = new DnsmasqConfig();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pid = file_get_contents($this->dnsmasqConfig->getPidFile());
        if ($pid >= 0) {
            $output->writeln("dnsmasq is running with PID:$pid");
        } else {
            $output->writeln("dnsmasq is stopped");
        }

        return 0;
    }
}
