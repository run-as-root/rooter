<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RegisterEnvCommand extends Command
{

    public function configure()
    {
        $this->setName('env:register');
        $this->setDescription('Register a project');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectName = getenv('PROJECT_NAME');

        if (empty($projectName)) {
            $output->writeln("<error>PROJECT_NAME is not set. This command should be executed in a project context.</error>");
            return Command::FAILURE;
        }

        $data = [
            'name' => $projectName,
            'path' => ROOTER_PROJECT_ROOT,
            'host' => getenv('PROJECT_HOST') ?? '',
            'httpPort' => getenv('DEVENV_HTTP_PORT') ?? '',
            'httpsPort' => getenv('DEVENV_HTTPS_PORT') ?? '',
            'dbPort' => getenv('DEVENV_DB_PORT') ?? '',
            'mailhogSmtpPort' => getenv('DEVENV_MAILHOG_SMTP_PORT') ?? '',
            'mailhogUiPort' => getenv('DEVENV_MAILHOG_UI_PORT') ?? '',
            'redisPort' => getenv('DEVENV_REDIS_PORT') ?? '',
            'amqpPort' => getenv('DEVENV_AMQP_PORT') ?? '',
            'amqpManagementPort' => getenv('DEVENV_AMQP_MANAGEMENT_PORT') ?? '',
            'elasticsearchPort' => getenv('DEVENV_ELASTICSEARCH_PORT') ?? '',
        ];

        try {
            $configAsString = json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        } catch (\JsonException $e) {
            $output->writeln("<error>Failed to generate env config {$e->getMessage()}</error>");
            return Command::FAILURE;

        }

        $envConfigFile = ROOTER_HOME_DIR . '/environments/' . $projectName . '.json';
        file_put_contents($envConfigFile, $configAsString);

        $configRaw = file_get_contents($envConfigFile);

        if ($configRaw === false) {
            $output->writeln('<error>Failed to create env config</error>');
            return Command::FAILURE;
        }

        $output->writeln('<info>Env registered successfully.</info>');
        return Command::SUCCESS;
    }
}
