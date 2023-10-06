<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use RunAsRoot\Rooter\Config\DevenvConfig;
use RunAsRoot\Rooter\Manager\ProcessManager;
use RunAsRoot\Rooter\Repository\EnvironmentRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListEnvCommand extends Command
{
    public function __construct(
        private readonly DevenvConfig $devenvConfig,
        private readonly EnvironmentRepository $envRepository,
        private readonly ProcessManager $processManager
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('env:list');
        $this->setDescription('list all projects');
        $this->addOption('ports', '', InputOption::VALUE_NONE, 'show all ports');
        $this->addOption('running', '', InputOption::VALUE_NONE, 'filter only running environments');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $showPorts = $input->getOption('ports');
        $onlyRunning = $input->getOption('running');

        $envList = $this->envRepository->getList();

        $projects = [];
        foreach ($envList as $envData) {
            $pidFile = $this->devenvConfig->getPidFile($envData['path']);
            $status = $this->processManager->isRunning($pidFile) ? 'running' : 'stopped';
            if($status !== 'running' && $onlyRunning) {
                continue;
            }

            $project = [
                'Name' => $envData['name'] ?? '',
                'Type' => $envData['type'] ?? '',
                'Host' => $envData['host'] ?? '',
                'Status' => $status,
            ];

            if ($showPorts) {
                $httpPortStr = '';
                if ($envData['httpPort'] || $envData['httpsPort']) {
                    $httpPortStr = sprintf("http:  %s\nhttps: %s", $envData['httpPort'] ?? '', $envData['httpsPort'] ?? '');
                }
                $mailStr = '';
                if (isset($envData['mailSmtpPort']) || isset($envData['mailUiPort'])) {
                    $mailStr = sprintf("smtp: %s\nui:   %s", $envData['mailSmtpPort'] ?? '', $envData['mailUiPort'] ?? '');
                }
                $amqpStr = '';
                if ($envData['amqpPort'] || $envData['amqpManagementPort']) {
                    $amqpStr = sprintf("tcp: %s\nui:  %s", $envData['amqpPort'] ?? '', $envData['amqpManagementPort'] ?? '');
                }

                $project['HTTP'] = $httpPortStr;
                $project['db'] = sprintf("%s", $envData['dbPort'] ?? '');
                $project['Mail'] = $mailStr;
                $project['Redis'] = sprintf("%s", $envData['redisPort'] ?? '');
                $project['AMQP'] = $amqpStr;
                $project['Elastic'] = sprintf("%s", $envData['elasticsearchPort'] ?? '');
                $project['ProcessCompose'] = sprintf("%s", $envData['processComposePort'] ?? '');
            }

            if (count($projects) > 0) {
                $projects[] = new TableSeparator();
            }

            $projects[] = $project;
        }

        $headers = ['Name', 'Host', 'Status'];
        if ($showPorts) {
            $headers = array_merge($headers, ['HTTP', 'db', 'Mail', 'Redis', 'AMQP', 'Elastic', 'ProcessCompose',]);
        }

        $table = new Table($output);
        $table->setStyle('box');
        $table->setHeaderTitle('environments');
        $table->setHeaders($headers);
        $table->setRows($projects);
        $table->render();

        return self::SUCCESS;
    }

}
