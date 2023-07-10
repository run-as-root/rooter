<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use RunAsRoot\Rooter\Config\RooterConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowEnvCommand extends Command
{
    public function __construct(private readonly RooterConfig $rooterConfig)
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('env:show');
        $this->setDescription('Show env settings');
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the env');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filename = $input->getArgument('name');
        $envFile = "{$this->rooterConfig->getEnvironmentDir()}/$filename.json";

        if (!file_exists($envFile)) {
            $output->writeln("<error>Unknown environment $filename</error>");
            return Command::FAILURE;
        }

        $jsonData = file_get_contents($envFile);
        try {
            $envData = json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $output->writeln("Invalid JSON format. Exception:{$e->getMessage()}");
            return Command::FAILURE;
        }

        if (!$envData) {
            $output->writeln('Invalid JSON format.');
            return Command::FAILURE;
        }

        $attributes = [
            'name',
            'path',
            'host',
            'httpPort',
            'httpsPort',
            'dbPort',
            'mailhogSmtpPort',
            'mailhogUiPort',
            'redisPort',
            'amqpPort',
            'amqpManagementPort',
            'elasticsearchPort',
        ];

        $table = new Table($output);
        $table->setStyle('box');

        $table->setHeaderTitle('Environment Variables');
        $table->setHeaders(['Variable', 'Value']);

        foreach ($attributes as $attributeKey) {
            $value = $envData[$attributeKey] ?? '';
            $table->addRow([$attributeKey, $value]);
        }

        $table->render();

        return Command::SUCCESS;
    }
}

