<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Repository;

use RunAsRoot\Rooter\Config\RooterConfig;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(lazy: true)]
class EnvironmentRepository
{
    public function __construct(private readonly RooterConfig $rooterConfig)
    {
    }

    /**
     * @throws \JsonException
     */
    public function getByName(string $projectName): array
    {
        $jsonData = file_get_contents("{$this->rooterConfig->getEnvironmentsDir()}/$projectName.json");

        return json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws \JsonException
     */
    public function getList(): array
    {
        $jsonFiles = glob("{$this->rooterConfig->getEnvironmentsDir()}/*.json");

        $jsonFiles = $jsonFiles === false ? [] : $jsonFiles;

        $projects = [];
        foreach ($jsonFiles as $jsonFile) {
            $jsonData = file_get_contents($jsonFile);

            $envData = json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);

            $projects[] = $envData;
        }

        return $projects;
    }

    /**
     * @throws \RuntimeException
     */
    public function register(string $projectName): void
    {
        $data = [
            'name' => $projectName,
            'type' => getenv('ROOTER_ENV_TYPE') ?? '',
            'path' => $this->rooterConfig->getEnvironmentRootDir(),
            'devenvProfile' => getenv('DEVENV_PROFILE') ?? '',
            'host' => getenv('PROJECT_HOST') ?? '',
            'httpPort' => getenv('DEVENV_HTTP_PORT') ?? '',
            'httpsPort' => getenv('DEVENV_HTTPS_PORT') ?? '',
            'dbPort' => getenv('DEVENV_DB_PORT') ?? '',
            'mailSmtpPort' => getenv('DEVENV_MAIL_SMTP_PORT') ?? '',
            'mailUiPort' => getenv('DEVENV_MAIL_UI_PORT') ?? '',
            'redisPort' => getenv('DEVENV_REDIS_PORT') ?? '',
            'amqpPort' => getenv('DEVENV_AMQP_PORT') ?? '',
            'amqpManagementPort' => getenv('DEVENV_AMQP_MANAGEMENT_PORT') ?? '',
            'elasticsearchPort' => getenv('DEVENV_ELASTICSEARCH_PORT') ?? '',
            'elasticsearchTcpPort' => getenv('DEVENV_ELASTICSEARCH_TCP_PORT') ?? '',
            'processComposePort' => getenv('DEVENV_PROCESS_COMPOSE_PORT') ?? '',
        ];

        $this->save($data);
    }

    /**
     * @throws \RuntimeException
     */
    public function save(array $data): void
    {
        try {
            $configAsString = json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        } catch (\JsonException $e) {
            throw new \RuntimeException('error during json encode', $e->getCode(), $e);
        }

        $envConfigFile = "{$this->rooterConfig->getEnvironmentsDir()}/{$data['name']}.json";
        file_put_contents($envConfigFile, $configAsString);

        if (file_get_contents($envConfigFile) === false) {
            throw new \RuntimeException('environment configuration file is empty');
        }
    }

    public function delete(string $projectName): void
    {
        $envConfigFile = "{$this->rooterConfig->getEnvironmentsDir()}/$projectName.json";
        if (!is_file($envConfigFile)) {
            throw new \RuntimeException("file does not exist '$envConfigFile'");
        }

        unlink($envConfigFile);
    }
}
