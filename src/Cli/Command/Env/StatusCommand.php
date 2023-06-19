<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use RunAsRoot\Rooter\Config\DevenvConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StatusCommand extends Command
{
    private DevenvConfig $devenvConfig;

    public function configure()
    {
        $this->setName('env:status');
        $this->setDescription('show status of env');
    }
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->devenvConfig = new DevenvConfig();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pid = $this->getPidFromFile($this->devenvConfig->getPidFile());

        $status = $this->isProcessRunning($pid) ? 'running' : 'stopped';

        $table = new Table($output);
        $table->setStyle('box');
        $table->setHeaders(['name', 'status', 'pid']);
        $table->setRows([
            ['devenv', $status, $pid],
        ]);
        $table->render();

        return 0;
    }

    private function getPidFromFile(string $pidFile): string
    {
        return trim(file_get_contents($pidFile));
    }

    private function isProcessRunning(string $pid): bool
    {
        if (empty($pid)) {
            return false;
        }
        return posix_kill((int)$pid, 0);
    }
}
