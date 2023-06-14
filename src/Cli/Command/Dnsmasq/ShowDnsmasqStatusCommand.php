<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Dnsmasq;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowDnsmasqStatusCommand extends Command
{
    public function configure()
    {
        $this->setName('dnsmasq:status');
        $this->setDescription('Show dnsmasq status');
        $this->setHidden();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pid = file_get_contents(ROOTER_HOME_DIR . '/dnsmasq/dnsmasq.pid');
        if ($pid >= 0) {
            $output->writeln("dnsmasq is running with PID:$pid");
        } else {
            $output->writeln("dnsmasq is stopped");
        }

        return 0;
    }
}
