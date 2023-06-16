<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Dnsmasq;

use RunAsRoot\Rooter\Config\DnsmasqConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class StartDnsmasqCommand extends Command
{
    private DnsmasqConfig $dnsmasqConfig;

    public function configure()
    {
        $this->setName('dnsmasq:start');
        $this->setDescription('Run dnsmasq in background');
        $this->setHidden();
    }
    
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->dnsmasqConfig = new DnsmasqConfig();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pidFile = $this->dnsmasqConfig->getPidFile();

        $pid = null;
        if (is_file($pidFile)) {
            $pid = file_get_contents($pidFile);
        }
        if ($pid > 0) {
            $output->writeln("dnsmasq is already running with PID:$pid");

            return 1;
        }

        $DNSMASQ_BIN = $this->dnsmasqConfig->getDnsmasqkBin();
        $dnsmasqConf = $this->dnsmasqConfig->getDnsmasqConf();
        $command = "$DNSMASQ_BIN --conf-file=$dnsmasqConf --no-daemon";

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(0);
        $process->setOptions(['create_new_console' => 1]);

        $process->start();

        sleep(2); # we need to wait a moment here

        $pid = $process->getPid();

        file_put_contents($pidFile, $pid);

        $output->writeln("<info>dnsmasq is running with PID:$pid</info>");

        return 0;
    }
}
