<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Repository;

use RunAsRoot\Rooter\Config\RooterConfig;

class EnvironmentRepository
{
    private RooterConfig $rooterConfig;

    public function __construct()
    {
        $this->rooterConfig = new RooterConfig();
    }

    /**
     * @throws \JsonException
     */
    public function getByName(string $projectName): array
    {
        $jsonData = file_get_contents("{$this->rooterConfig->getEnvironmentDir()}/$projectName.json");

        return json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws \JsonException
     */
    public function getList(): array
    {
        $jsonFiles = glob("{$this->rooterConfig->getEnvironmentDir()}/*.json");

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

        $envConfigFile = "{$this->rooterConfig->getEnvironmentDir()}/{$data['name']}.json";
        file_put_contents($envConfigFile, $configAsString);

        if (file_get_contents($envConfigFile) === false) {
            throw new \RuntimeException('environment configuration file is empty');
        }
    }

    public function delete(string $projectName): void
    {
        $envConfigFile = "{$this->rooterConfig->getEnvironmentDir()}/$projectName.json";
        if (!is_file($envConfigFile)) {
            throw new \RuntimeException("file does not exist '$envConfigFile'");
        }

        unlink($envConfigFile);
    }
}
