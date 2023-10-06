<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use RunAsRoot\Rooter\Manager\PortManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'env:ports:init',
    description: 'find free port for each service and write them to .env',
)]
class InitEnvPortsCommand extends Command
{
    public function __construct(private readonly PortManager $portManager)
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addOption('write', 'w', InputOption::VALUE_NONE, 'write contents in .env');
        $this->addOption('print', 'p', InputOption::VALUE_NONE, 'print the added env variables');
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Finding ports ...');

        $ports = $this->portManager->findFreePortsForRanges();
        $envVariables = [];
        foreach ($ports as $type => $port) {
            $envVariables["DEVENV_{$type}_PORT"] = $port;
        }

        if ($input->getOption('write')) {
            $output->writeln('Writing ports to .env');
            $this->writeToEnvFile($envVariables);
        }

        $output->writeln('Ports available for this environment:');
        foreach ($envVariables as $varName => $varValue) {
            $output->writeln("$varName=$varValue");
        }

        return Command::SUCCESS;
    }

    private function writeToEnvFile(array $envVariables): void
    {
        $variablesToAdd = $envVariables; // copy to have a list of remaining variables to add

        $envFile = ROOTER_PROJECT_ROOT . "/.env";
        $lines = is_file($envFile) ? file($envFile) : [];

        // Clean array from variables prefixed with DEVENV_ or ROOTER_
        $envFileData = [];
        foreach ($lines as $line) {
            if (!str_starts_with($line, 'DEVENV_') || !str_starts_with($line, 'ROOTER_')) {
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

