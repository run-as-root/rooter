<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Dnsmasq;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StopDnsmasqCommand extends Command
{
    public function configure()
    {
        $this->setName('dnsmasq:stop');
        $this->setDescription('Stop dnsmasq');
        $this->setHidden();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pidFile = ROOTER_HOME_DIR . '/dnsmasq/dnsmasq.pid';

        $pid = null;
        if (is_file($pidFile)) {
            $pid = file_get_contents($pidFile);
        }
        if ($pid <= 0) {
            $output->writeln("<error>There is no dnsmasq running for PID:$pid</error>");

            return 1;
        }

        if ($ok = proc_open(sprintf('kill -%d %d', 9, $pid), [2 => ['pipe', 'w']], $pipes)) {
            $ok = false === fgets($pipes[2]);
        }

        if (!$ok) {
            $output->writeln("<error>Could not stop dnsmasq with PID:$pid</error>");
        } else {
            $output->writeln("dnsmasq process with PID:$pid was stopped");
        }

        file_put_contents($pidFile, '');

        return 0;
    }
}
