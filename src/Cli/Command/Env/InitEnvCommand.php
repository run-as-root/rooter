<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use RunAsRoot\Rooter\Cli\Output\EnvironmentsRenderer;
use RunAsRoot\Rooter\Config\RooterConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InitEnvCommand extends Command
{
    public function __construct(
        private readonly RooterConfig $rooterConfig,
        private readonly EnvironmentsRenderer $environmentsRenderer
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('env:init');
        $this->setDescription('Initialise environment for current directory');
        $this->addArgument('type', InputArgument::REQUIRED, 'The system you want to initialise');
        $this->addUsage('magento2');
        $this->addUsage('magento2 --name=my-project-name');
        $this->addUsage('shopware6');
        $this->addOption(
            'name', '', InputOption::VALUE_REQUIRED, 'the name of the environment you are creating. Defaults to the current directory name'
        );
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'force overwriting of env files');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Prepare
        $type = $input->getArgument('type');

        $envTmplDir = "{$this->rooterConfig->getEnvironmentTemplatesDir()}/$type";
        if (!is_dir($envTmplDir)) {
            $output->writeln("<error>unknown environment type: $type</error>");
            $this->environmentsRenderer->render($input, $output);
            return Command::FAILURE;
        }

        // @todo make this more user-friendly: show files that would be overwritten, let the user decide with force

        // files to copy to project
        // @todo this is hardcoded and has to be the same for all projects, could be dynamically configured per environment template
        $files = [
            ".envrc" => ".envrc",
            "devenv.nix" => "devenv.nix",
            "devenv.yaml" => "devenv.yaml",
        ];

        $isForce = (bool)$input->getOption('force');
        if (!$isForce && !$this->canInitialiseEnvironment($files)) {
            $output->writeln("<info>Seems like the environment was already initialised.</info>");
            $output->writeln("<info>If you still want to continue please use --force.</info>");
            return Command::FAILURE;
        }

        $projectName = $input->getOption('name') ?? basename(ROOTER_PROJECT_ROOT);

        $vars = [
            'PROJECT_NAME' => $projectName,
            'PROJECT_HOST' => "$projectName.rooter.test",
        ];

        $searchStrings = [];
        $replaceStrings = [];
        foreach ($vars as $variable => $value) {
            $searchStrings[] = '${' . $variable . '}';
            $replaceStrings[] = $value;
        }

        // Copy files to project replacing placeholders
        foreach ($files as $sourceFile => $targetFile) {
            $sourcePath = "$envTmplDir/$sourceFile";
            $targetPath = ROOTER_PROJECT_ROOT . "/$targetFile";

            $sourceContent = file_get_contents($sourcePath);

            $targetContent = str_replace($searchStrings, $replaceStrings, $sourceContent);

            file_put_contents($targetPath, $targetContent);

            $output->writeln("$sourceFile => $targetFile");
        }

        return 0;
    }

    private function canInitialiseEnvironment(array $files): bool
    {
        foreach ($files as $targetFile) {
            $targetPath = ROOTER_PROJECT_ROOT . "/" . $targetFile;
            // If one file is found we will not initialise
            if (is_file($targetPath)) {
                return false;
            }
        }
        return true;
    }

}
