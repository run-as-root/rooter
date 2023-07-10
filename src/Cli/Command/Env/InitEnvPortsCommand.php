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
        $this->addOption('overwrite', 'o', InputOption::VALUE_NONE, 'overwrite contents in .env');
        $this->addOption('print', 'p', InputOption::VALUE_NONE, 'print the added env variables');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $projectName = getenv('PROJECT_NAME');

        if (empty($projectName)) {
            $output->writeln("<error>PROJECT_NAME is not set. This command should be executed in a project context.</error>");
            return Command::FAILURE;
        }

        $output->writeln('Initialising ports …');

        $types = ['HTTP', 'HTTPS', 'DB', 'MAILHOG_SMTP', 'MAILHOG_UI', 'REDIS', 'AMQP', 'AMQP_MANAGEMENT', 'ELASTICSEARCH',];

        foreach ($types as $type) {
            $port = $this->portManager->findFreePort($type);
            $ports[$type] = $port;
            $output->writeln("$type: $port");
        }

        if ($input->getOption('overwrite')) {
            $this->writeToEnvFile($ports);
        }

        if ($input->getOption('print')) {
            $envContent = '';
            foreach ($ports as $type => $port) {
                $envContent .= "DEVENV_{$type}_PORT=$port" . PHP_EOL;
            }
            $output->writeln('');
            $output->writeln('.env content');
            $output->writeln($envContent);
        }

        return Command::SUCCESS;
    }

    private function writeToEnvFile(array $ports): void
    {
        // Prepare array with ENV var name and value
        $portsEnvs = [];
        foreach ($ports as $type => $port) {
            $portsEnvs["DEVENV_{$type}_PORT"] = $port;
        }
        $portsToAdd = $portsEnvs;

        $envFile = ROOTER_PROJECT_ROOT . "/.env";
        $lines = file($envFile);

        // Clean array from DEVENV PORT variables
        $envFileData = [];
        foreach ($lines as $line) {
            if (!str_starts_with($line, 'DEVENV_')) {
                $envFileData[] = $line;
                continue;
            }

            foreach ($portsEnvs as $envName => $port) {
                if (preg_match("/$envName=.*/", $line)) {
                    $envFileData[] = "$envName=$port" . PHP_EOL;
                    unset($portsToAdd[$envName]);
                    break;
                }
            }
        }

        // Add remaining ENV vars to the .env
        foreach ($portsToAdd as $envName => $port) {
            $envFileData[] = "$envName=$port" . PHP_EOL;
        }

        file_put_contents($envFile, implode('', $envFileData));
    }
}

