<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use RunAsRoot\Rooter\Config\DevenvConfig;
use RunAsRoot\Rooter\Config\RooterConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListEnvCommand extends Command
{
    private RooterConfig $rooterConfig;
    private DevenvConfig $devenvConfig;

    public function configure()
    {
        $this->setName('env:list');
        $this->setDescription('list all projects');
        $this->addOption('ports', '', InputOption::VALUE_NONE, 'show all ports');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->rooterConfig = new RooterConfig();
        $this->devenvConfig = new DevenvConfig();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $showPorts = $input->getOption('ports');

        $jsonFiles = glob("{$this->rooterConfig->getEnvironmentDir()}/*.json");

        $jsonFiles = $jsonFiles === false ? [] : $jsonFiles;

        $projects = [];
        foreach ($jsonFiles as $jsonFile) {
            $jsonData = file_get_contents($jsonFile);

            try {
                $envData = json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $output->writeln("<error>Could not decode '$jsonFile' : '{$e->getMessage()}'</error>");
                continue;
            }

            $pid = $this->getPidFromFile($this->devenvConfig->getPidFile($envData['path']));
            $status = $this->isProcessRunning($pid) ? 'running' : 'stopped';

            $project = [
                'Name' => $envData['name'] ?? '',
                'Host' => $envData['host'] ?? '',
                'Status' => $status,
            ];

            if ($showPorts) {
                $project['HTTP'] = sprintf("http:  %s\nhttps: %s", $envData['httpPort'] ?? '', $envData['httpsPort'] ?? '');
                $project['db'] = sprintf("%s", $envData['dbPort'] ?? '');
                $project['Mailhog'] = sprintf("smtp: %s\nui:   %s", $envData['mailhogSmtpPort'] ?? '', $envData['mailhogUiPort'] ?? '');
                $project['Redis'] = sprintf("%s", $envData['redisPort'] ?? '');
                $project['AMQP'] = sprintf("tcp: %s\nui:  %s", $envData['amqpPort'] ?? '', $envData['amqpManagementPort'] ?? '');
                $project['Elastic'] = sprintf("%s", $envData['elasticsearchPort'] ?? '');
            }

            if (count($projects) > 0) {
                $projects[] = new TableSeparator();
            }

            $projects[] = $project;
        }

        $headers = ['Name', 'Host', 'Status'];
        if ($showPorts) {
            $headers = array_merge($headers, ['HTTP', 'db', 'Mailhog', 'Redis', 'AMQP', 'Elastic',]);
        }

        $table = new Table($output);
        $table->setStyle('box');
        $table->setHeaderTitle('project list');
        $table->setHeaders($headers);
        $table->setRows($projects);
        $table->render();

        return self::SUCCESS;
    }

    private function getPidFromFile(string $pidFile): string
    {
        if (!is_file($pidFile)) {
            return "";
        }
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
