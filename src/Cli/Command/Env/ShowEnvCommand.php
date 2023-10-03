<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use RunAsRoot\Rooter\Repository\EnvironmentRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowEnvCommand extends Command
{
    public function __construct(
        private readonly EnvironmentRepository $envRepository
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('env:show');
        $this->setDescription('Show env settings');
        $this->addArgument('name', InputArgument::OPTIONAL, 'The name of the env');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $projectName = $input->getArgument('name') ?? getenv('PROJECT_NAME');
        if (!$projectName) {
            $output->writeln("<error>Please provide a project-name or execute in a project context.</error>");
            return Command::FAILURE;
        }

        try {
            $envData = $this->envRepository->getByName($projectName);
        } catch (\JsonException $e) {
            $output->writeln("<error>Could not get environment config: invalid json {$e->getMessage()}</error>");
            return Command::FAILURE;
        }

        $attributes = [
            'name',
            'path',
            'host',
            'httpPort',
            'httpsPort',
            'dbPort',
            'mailSmtpPort',
            'mailUiPort',
            'redisPort',
            'amqpPort',
            'amqpManagementPort',
            'elasticsearchPort',
            'elasticsearchTcpPort',
            'processComposePort',
        ];

        $table = new Table($output);
        $table->setStyle('box');
        $table->setHeaderTitle('Environment Variables');
        $table->setHeaders(['Variable', 'Value']);

        foreach ($attributes as $attributeKey) {
            $value = $envData[$attributeKey] ?? '';
            if (!empty($value) || $output->isVerbose()) {
                $table->addRow([$attributeKey, $value]);
            }
        }

        $table->render();

        return Command::SUCCESS;
    }

}

