<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Output;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\OutputInterface;

class EnvironmentConfigRenderer
{
    public function render(array $envData, OutputInterface $output): void
    {
        $table = new Table($output);
        $table->setStyle('box');
        $table->setHeaders(['setting', 'value']);
        $table->setRows([
            ['type', $envData['type']],
            ['path', $envData['path']],
            ['host', $envData['host']],
            new TableSeparator(),
            ['process-compose', $envData['processComposePort']],
            ['httpd', "http:{$envData['httpPort']} (https:{$envData['httpsPort']})"],
            ['db', $envData['dbPort']],
            ['redis', $envData['redisPort']],
            ['AMQP', "{$envData['amqpPort']} (admin:{$envData['amqpManagementPort']})"],
            ['elasticsearch', "{$envData['elasticsearchPort']} (tcp:{$envData['elasticsearchTcpPort']})"],
            ['mail', "smtp:{$envData['mailSmtpPort']} (ui:{$envData['mailUiPort']})"],
        ]);
        $table->render();
    }
}
