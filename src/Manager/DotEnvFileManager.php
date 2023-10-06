<?php

namespace RunAsRoot\Rooter\Manager;

use RunAsRoot\Rooter\Repository\EnvironmentRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(lazy: true)]
class DotEnvFileManager
{
    public function __construct()
    {
    }

    public function write(array $envVariables, string $envFile=''): void
    {
        $variablesToAdd = $envVariables; // copy to have a list of remaining variables to add

        $envFile = $envFile ?: ROOTER_PROJECT_ROOT . "/.env";
        $lines = is_file($envFile) ? file($envFile) : [];

        // Clean array from variables prefixed with DEVENV_ or ROOTER_
        $envFileData = [];
        foreach ($lines as $line) {
            if (!str_starts_with($line, 'DEVENV_') && !str_starts_with($line, 'ROOTER_')) {
                $envFileData[] = $line;
                continue;
            }

            foreach ($envVariables as $varName => $varValue) {
                if (preg_match("/$varName=.*/", $line)) {
                    $envFileData[] = "$varName=$varValue" . PHP_EOL;
                    unset($variablesToAdd[$varName]);
                    break;
                }
            }
        }

        // Add remaining ENV vars to the .env
        foreach ($variablesToAdd as $varName => $varValue) {
            $envFileData[] = "$varName=$varValue" . PHP_EOL;
        }

        file_put_contents($envFile, implode('', $envFileData));
    }

}
