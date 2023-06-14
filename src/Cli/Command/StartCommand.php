<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command;

use RunAsRoot\Rooter\Cli\Command\Dnsmasq\StartDnsmasqCommand;
use RunAsRoot\Rooter\Cli\Command\Traefik\StartTraefikCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StartCommand extends Command
{
    public function configure()
    {
        $this->setName('start');
        $this->setDescription('start rooter processes');
    }

    /**
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dnsmasqCommand = new StartDnsmasqCommand();
        $dnsmasqCommand->run(new ArrayInput([]), $output);

        $traefikCommand = new StartTraefikCommand();
        $traefikCommand->run(new ArrayInput([]), $output);

        return 0;
    }
}
