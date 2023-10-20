<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use Exception;
use RunAsRoot\Rooter\Config\RooterConfig;
use RunAsRoot\Rooter\Manager\DotEnvFileManager;
use RunAsRoot\Rooter\Manager\PortManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InitEnvCommand extends Command
{
    public function __construct(
        private readonly RooterConfig $rooterConfig,
        private readonly PortManager $portManager,
        private readonly DotEnvFileManager $dotEnvFileManager
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('env:init');
        $this->setDescription('Initialise local environment for an already configured rooter project');
        $this->addArgument('type', InputArgument::OPTIONAL, 'The environment type you want to initialise');
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $envFile = ROOTER_PROJECT_ROOT . "/.env";
        if (is_file($envFile)) {
            $io->info("Environment already initialised.");
            return Command::FAILURE;
        }

        // get environment type
        $type = $input->getArgument('type');
        if (empty($type)) {
            $type = $io->choice('Please select the environment type:', $this->rooterConfig->getEnvironmentTypes());
        }
        $io->writeln("Initialising environment of type: $type");

        // Create env file
        $envVars = array_merge(
            ['ROOTER_ENV_TYPE' => $type],
            $this->portManager->findFreePortsForRanges(true)
        );
        $this->dotEnvFileManager->write($envVars);

        $io->success("Environment initialised. ENV variables written to .env");

        return Command::SUCCESS;
    }

}
