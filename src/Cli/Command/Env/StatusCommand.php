<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use RunAsRoot\Rooter\Config\DevenvConfig;
use RunAsRoot\Rooter\Manager\ProcessManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StatusCommand extends Command
{
    private DevenvConfig $devenvConfig;
    private ProcessManager $processManager;

    public function configure()
    {
        $this->setName('env:status');
        $this->setDescription('show status of env');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->devenvConfig = new DevenvConfig();
        $this->processManager = new ProcessManager();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pid = $this->processManager->getPidFromFile($this->devenvConfig->getPidFile());

        $status = $this->processManager->isRunningByPid($pid) ? 'running' : 'stopped';

        $table = new Table($output);
        $table->setStyle('box');
        $table->setHeaders(['name', 'status', 'pid']);
        $table->setRows([
            ['devenv', $status, $pid],
        ]);
        $table->render();

        return 0;
    }

}
