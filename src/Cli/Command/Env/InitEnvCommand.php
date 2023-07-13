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
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');

        // get environment type
        $type = $input->getArgument('type');
        if (empty($type)) {
            $question = new ChoiceQuestion('Please select the environment', $this->rooterConfig->getEnvironmentTypes());
            $question->setErrorMessage('environment type %s is unknown.');

            $type = $helper->ask($input, $output, $question);
        }
        $output->writeln("Initialising environment of type: $type");

        // Verify environment template dir
        $envTmplDir = "{$this->rooterConfig->getEnvironmentTemplatesDir()}/$type";
        if (!is_dir($envTmplDir)) {
            $output->writeln("<error>environment templates directory not found for type: $type</error>");
            $this->environmentsRenderer->render($input, $output);
            return Command::FAILURE;
        }

        // files to copy to project
        // @todo this is hardcoded and has to be the same for all projects, could be dynamically configured per environment template
        $files = [
            ".envrc" => ".envrc",
            "devenv.nix" => "devenv.nix",
            "devenv.yaml" => "devenv.yaml",
        ];

        $isForce = (bool)$input->getOption('force');
        $envBaseDir = getcwd();
        $projectName = $input->getOption('name') ?? dirname($envBaseDir);

        // Check files
        $filesToWrite = [];
        foreach ($files as $sourceFile => $targetFile) {
            $targetPath = $envBaseDir . "/$targetFile";

            if (!$isForce && is_file($targetPath)) {
                $question = new ConfirmationQuestion("Overwrite $targetFile ? (y/n): ", false);
                if (!$helper->ask($input, $output, $question)) {
                    continue;
                }
            }

            $filesToWrite[$sourceFile] = $targetFile;
        }

        if (empty($filesToWrite)) {
            $output->writeln('No files were changed');
            return Command::SUCCESS;
        }

        // Copy files to project replacing placeholders
        foreach ($filesToWrite as $sourceFile => $targetFile) {
            $sourcePath = "$envTmplDir/$sourceFile";
            $targetPath = $envBaseDir . "/$targetFile";

            $sourceContent = file_get_contents($sourcePath);
            if ($sourceContent === false) {
                $output->writeln("<error>Could not read file $sourcePath</error>");
                continue;
            }

            $targetContent = $this->renderContent($projectName, $sourceContent);

            file_put_contents($targetPath, $targetContent);

            $output->writeln("Copied $targetFile");
        }

        return Command::SUCCESS;
    }

    private function renderContent(mixed $projectName, string $sourceContent): string
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
