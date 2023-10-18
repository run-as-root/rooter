<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use RunAsRoot\Rooter\Cli\Output\EnvironmentsRenderer;
use RunAsRoot\Rooter\Config\RooterConfig;
use RunAsRoot\Rooter\Manager\DotEnvFileManager;
use RunAsRoot\Rooter\Manager\PortManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class InitEnvCommand extends Command
{
    /**
     * files to copy to project
     * @todo this is hardcoded and has to be the same for all projects, could be dynamically configured per environment template
     */
    private array $files = [
        ".envrc" => ".envrc",
        "devenv.nix" => "devenv.nix",
        "devenv.yaml" => "devenv.yaml",
    ];

    public function __construct(
        private readonly RooterConfig $rooterConfig,
        private readonly EnvironmentsRenderer $environmentsRenderer,
        private readonly PortManager $portManager,
        private readonly DotEnvFileManager $dotEnvFileManager
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('env:init');
        $this->setDescription('Initialise environment for current directory');
        $this->addArgument('type', InputArgument::OPTIONAL, 'The system you want to initialise');
        $this->addOption(
            'name', '', InputOption::VALUE_REQUIRED, 'the name of the environment you are creating. Defaults to the current directory name'
        );
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'force overwriting of env files');

        $this->addUsage('magento2');
        $this->addUsage('magento2 --name=my-project-name');
        $this->addUsage('shopware6');
        $this->addUsage('laravel');
        $this->addUsage('magento1');
        $this->addUsage('symfony');
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');
        $projectName = $input->getOption('name') ?? basename(getcwd());

        // get environment type
        $type = (string)$input->getArgument('type');
        if (empty($type)) {
            $question = new ChoiceQuestion('Please select the environment type:', $this->rooterConfig->getEnvironmentTypes());
            $question->setErrorMessage('environment type %s is unknown.');

            $type = $helper->ask($input, $output, $question);
        }
        $output->writeln("Initialising environment of type: $type");

        // Verify environment template dir
        if (!is_dir($this->rooterConfig->getEnvironmentTemplatesDirForType($type))) {
            $output->writeln("<error>environment templates directory not found for type: $type</error>");
            $this->environmentsRenderer->render($input, $output);
            return Command::FAILURE;
        }

        // Ask for changes
        [$filesToCopy, $filesToUpdate] = $this->askForChanges($input, $output);

        if (empty($filesToCopy) && empty($filesToUpdate)) {
            $output->writeln('No files created or updated.');
            return Command::SUCCESS;
        }

        // Copy files to project replacing placeholders
        if (!empty($filesToCopy)) {
            $this->copyFilesFromTemplate($filesToCopy, $type, $projectName, $output);
        }

        // Update .env file
        if (array_key_exists('.env', $filesToUpdate)) {
            $this->updateEnvFile($filesToUpdate['.env'], $type, $output);
        }

        return Command::SUCCESS;
    }

    private function askForChanges(InputInterface $input, OutputInterface $output): array
    {
        $envBaseDir = getcwd();
        $helper = $this->getHelper('question');

        $isForce = (bool)$input->getOption('force');

        // Files to copy
        $filesToCopy = [];
        foreach ($this->files as $sourceFile => $targetFile) {
            $targetPath = $envBaseDir . "/$targetFile";

            if (!$isForce && is_file($targetPath)) {
                $question = new ConfirmationQuestion("Overwrite $targetFile ? (y/N): ", false);
                if (!$helper->ask($input, $output, $question)) {
                    continue;
                }
            }

            $filesToCopy[$sourceFile] = $targetFile;
        }

        // Files to update
        $filesToUpdate = [];
        $envFile = ROOTER_PROJECT_ROOT . "/.env";
        if (!$isForce && is_file($envFile)) {
            $question = new ConfirmationQuestion("Update .env ? (y/N): ", false);
            if ($helper->ask($input, $output, $question)) {
                $filesToUpdate['.env'] = $envFile;
            }
        } else {
            $filesToUpdate['.env'] = $envFile;
        }

        return [$filesToCopy, $filesToUpdate];
    }

    private function copyFilesFromTemplate(array $filesToWrite, string $type, string $projectName, OutputInterface $output): void
    {
        $envTmplDir = $this->rooterConfig->getEnvironmentTemplatesDirForType($type);
        $envBaseDir = getcwd();
        foreach ($filesToWrite as $sourceFile => $targetFile) {
            $sourcePath = "$envTmplDir/$sourceFile";
            $targetPath = $envBaseDir . "/$targetFile";

            $sourceContent = file_get_contents($sourcePath);
            if ($sourceContent === false) {
                $output->writeln("<error>Could not read file $sourcePath</error>");
                continue;
            }

            $targetContent = $this->renderContent($projectName, $sourceContent);

            // create backup first
            if (is_file($targetPath)) {
                copy($targetPath, "$targetPath.backup");
                $output->writeln("Backup $targetFile to $targetPath.backup");
            }

            file_put_contents($targetPath, $targetContent);

            $output->writeln("Copied $targetFile");
        }
    }

    /**
     * @throws \Exception
     */
    private function updateEnvFile(string $envFile, string $type, OutputInterface $output): void
    {
        // Create backup
        if (is_file($envFile)) {
            copy($envFile, "$envFile.backup");
            $output->writeln("Backup $envFile to $envFile.backup");
        }

        // Create or Update env file
        $output->writeln('Writing ENV variables to .env');
        $envVars = array_merge(
            ['ROOTER_ENV_TYPE' => $type],
            $this->portManager->findFreePortsForRanges(true)
        );
        $this->dotEnvFileManager->write($envVars);
    }

    private function renderContent(string $projectName, string $sourceContent): string
    {
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

        return (string)str_replace($searchStrings, $replaceStrings, $sourceContent);
    }

}
