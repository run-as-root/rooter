<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use RunAsRoot\Rooter\Manager\PortManager;
use RunAsRoot\Rooter\Repository\EnvironmentRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckEnvPortsCommand extends Command
{
    public function __construct(
        private readonly PortManager $portManager,
        private readonly EnvironmentRepository $envRepository
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('env:ports:check');
        $this->setDescription('check ports configured for this environment');
        $this->addArgument('name', InputArgument::OPTIONAL, 'the name of the environment');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $projectName = $input->getArgument('name') ?? getenv('PROJECT_NAME');

        if (!$projectName) {
            $output->writeln("<error>PROJECT_NAME is not set. This command should be executed in a project context.</error>");
            return Command::FAILURE;
        }

        $output->writeln('Checking Ports …');

        $envData = $this->envRepository->getByName($projectName);

        $rows = [];
        if (isset($envData['httpPort']) && $envData['httpPort'] > 0) {
            $rows[] = ['HTTP', $envData['httpPort'], $this->portManager->isPortAvailable((int)$envData['httpPort'])];
        }
        if (isset($envData['httpsPort']) && $envData['httpsPort'] > 0) {
            $rows[] = ['HTTPS', $envData['httpsPort'], $this->portManager->isPortAvailable((int)$envData['httpsPort'])];
        }

        if (isset($envData['mailSmtpPort']) && $envData['mailSmtpPort'] > 0) {
            $rows[] = ['Mail SMTP', $envData['mailSmtpPort'], $this->portManager->isPortAvailable((int)$envData['mailSmtpPort'])];
            $rows[] = ['Mail UI', $envData['mailUiPort'], $this->portManager->isPortAvailable((int)$envData['mailUiPort'])];
        }

        if (isset($envData['dbPort']) && $envData['dbPort'] > 0) {
            $rows[] = ['db', $envData['dbPort'], $this->portManager->isPortAvailable((int)$envData['dbPort'])];
        }

        if (isset($envData['redisPort']) && $envData['redisPort'] > 0) {
            $rows[] = ['Redis', $envData['redisPort'], $this->portManager->isPortAvailable((int)$envData['redisPort'])];
        }

        if (isset($envData['amqpPort']) && $envData['amqpPort'] > 0) {
            $rows[] = ['AMQP', $envData['amqpPort'], $this->portManager->isPortAvailable((int)$envData['amqpPort'])];
        }
        if (isset($envData['amqpManagementPort']) && $envData['amqpManagementPort'] > 0) {
            $amqpManagementPort = $envData['amqpManagementPort'];
            $rows[] = ['AMQP Management', $amqpManagementPort, $this->portManager->isPortAvailable((int)$amqpManagementPort),];
        }

        if (isset($envData['elasticsearchPort']) && $envData['elasticsearchPort'] > 0) {
            $rows[] = ['Elastic', $envData['elasticsearchPort'], $this->portManager->isPortAvailable((int)$envData['elasticsearchPort'])];
        }
        if (isset($envData['elasticsearchTcpPort']) && $envData['elasticsearchTcpPort'] > 0) {
            $elasticsearchTcpPort = $envData['elasticsearchTcpPort'];
            $rows[] = ['Elastic TCP', $elasticsearchTcpPort, $this->portManager->isPortAvailable((int)$elasticsearchTcpPort),];
        }

        $table = new Table($output);
        $table->setStyle('box');
        $table->setHeaders(['name', 'port', 'status']);
        $table->addRows($rows);
        $table->render();

        return Command::SUCCESS;
    }
}

