<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Traefik;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StopTraefikCommand extends Command
{
    public function configure()
    {
        $this->setName('traefik:stop');
        $this->setDescription('Stop Traefik');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pidFile = ROOTER_HOME_DIR . '/traefik/traefik.pid';

        $pid = null;
        if (is_file($pidFile)) {
            $pid = file_get_contents($pidFile);
        }
        if ($pid <= 0) {
            $output->writeln("<error>There is no traefik running for PID:$pid</error>");

            return 1;
        }

        if ($ok = proc_open(sprintf('kill -%d %d', 9, $pid), [2 => ['pipe', 'w']], $pipes)) {
            $ok = false === fgets($pipes[2]);
        }

        if (!$ok) {
            $output->writeln("<error>Could not stop traefik with PID:$pid</error>");
        } else {
            $output->writeln("Traefik process with PID:$pid was stopped");
        }

        file_put_contents($pidFile, '');

        return 0;
    }
}
