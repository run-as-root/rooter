<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Output;

use RunAsRoot\Rooter\Cli\Output\Style\TitleGrayOutputStyle;
use RunAsRoot\Rooter\Config\DevenvConfig;
use RunAsRoot\Rooter\Manager\ProcessManager;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

readonly class EnvironmentListRenderer
{
    public function __construct(
        private DevenvConfig $devenvConfig,
        private ProcessManager $processManager
    ) {
    }

    public function render(
        array $environments,
        InputInterface $input,
        OutputInterface $output,
        bool $onlyRunning = false,
        bool $showPorts = false
    ): void {
        $io = new SymfonyStyle($input, $output);
        $io->block(messages: 'environments', style: TitleGrayOutputStyle::NAME, prefix: '  ', padding: true);

        $projects = [];
        foreach ($environments as $envData) {
            $pidFile = $this->devenvConfig->getPidFile($envData['path']);
            $status = $this->processManager->isRunning($pidFile) ? 'running' : 'stopped';
            if ($status !== 'running' && $onlyRunning) {
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
            }

            if (count($projects) > 0) {
                $projects[] = new TableSeparator();
            }

            $projects[] = $project;
        }

        $headers = ['Name', 'Type', 'Host', 'Status'];
        if ($showPorts) {
            $headers = array_merge($headers, ['HTTP', 'db', 'Mail', 'Redis', 'AMQP', 'Elastic',]);
        }

        $table = new Table($output);
        $table->setStyle('box');
        $table->setHeaders($headers);
        $table->setRows($projects);
        $table->render();
    }

}
