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

}
