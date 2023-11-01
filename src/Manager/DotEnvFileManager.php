<?php

namespace RunAsRoot\Rooter\Manager;

use RunAsRoot\Rooter\Config\RooterConfig;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(lazy: true)]
class DotEnvFileManager
{
    public function __construct(private readonly RooterConfig $rooterConfig)
    {
    }

    public function write(array $envVariables, string $envFile = ''): void
    {
        $variablesToAdd = $envVariables; // copy to have a list of remaining variables to add

        $envFile = $envFile ?: $this->rooterConfig->getEnvironmentEnvFile();
        $lines = is_file($envFile) ? file($envFile) : [];

        // Clean array from variables prefixed with DEVENV_ or ROOTER_
        $envFileData = [];
        foreach ($lines as $line) {
            if (!preg_match("/(#?(ROOTER_|DEVENV_).*)=/", $line)) {
                $envFileData[] = $line;
                continue;
            }

            $envVarReplaced = false;
            foreach ($envVariables as $varName => $varValue) {
                if (preg_match("/$varName=.*/", $line)) {
                    $comment = str_starts_with($line, '#') ? "#" : ''; // Keep the line commented out
                    $envFileData[] = "$comment$varName=$varValue" . PHP_EOL;
                    $envVarReplaced = true;
                    unset($variablesToAdd[$varName]);
                    break;
                }
            }
            // If the env var was not replaced, add the line to the .env unchanged
            if ($envVarReplaced === false) {
                $envFileData[] = $line;
            }
        }

        // Add remaining ENV vars to the .env
        foreach ($variablesToAdd as $varName => $varValue) {
            $envFileData[] = "$varName=$varValue" . PHP_EOL;
        }

        file_put_contents($envFile, implode('', $envFileData));
    }

}
