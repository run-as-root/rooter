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

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $projectName = $input->getArgument('name') ?? getenv('PROJECT_NAME');

        if (empty($projectName)) {
            $output->writeln("<error>PROJECT_NAME is not set. This command should be executed in a project context.</error>");
            return Command::FAILURE;
        }

        $output->writeln('Checking Ports …');

        $envData = $this->envRepository->getByName($projectName);

        $rows = [];
        $rows[] = ['HTTP', $envData['httpPort'], $this->portManager->isPortAvailable((int)$envData['httpPort'])];
        $rows[] = ['HTTPS', $envData['httpsPort'], $this->portManager->isPortAvailable((int)$envData['httpsPort'])];
        $rows[] = ['db', $envData['dbPort'], $this->portManager->isPortAvailable((int)$envData['dbPort'])];
        $rows[] = ['Mailhog SMTP', $envData['mailhogSmtpPort'], $this->portManager->isPortAvailable((int)$envData['mailhogSmtpPort'])];
        $rows[] = ['Mailhog UI', $envData['mailhogUiPort'], $this->portManager->isPortAvailable((int)$envData['mailhogUiPort'])];
        $rows[] = ['db', $envData['dbPort'], $this->portManager->isPortAvailable((int)$envData['dbPort'])];
        $rows[] = ['Redis', $envData['redisPort'], $this->portManager->isPortAvailable((int)$envData['redisPort'])];
        $rows[] = ['AMQP', $envData['amqpPort'], $this->portManager->isPortAvailable((int)$envData['amqpPort'])];
        $rows[] = ['AMQP Management', $envData['amqpManagementPort'], $this->portManager->isPortAvailable((int)$envData['amqpManagementPort'])];
        $rows[] = ['Elastic', $envData['elasticsearchPort'], $this->portManager->isPortAvailable((int)$envData['elasticsearchPort'])];

        $table = new Table($output);
        $table->setHeaders(['name', 'port', 'status']);
        $table->setStyle('box');
        $table->addRows($rows);
        $table->render();

        return Command::SUCCESS;
    }
}

