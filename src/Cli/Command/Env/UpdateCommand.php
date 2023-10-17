<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use RunAsRoot\Rooter\Cli\Command\Devenv\UpdateDevenvCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class UpdateCommand extends Command
{
    public function __construct(
        private readonly StopCommand $stopCommand,
        private readonly UpdateDevenvCommand $updateDevenvCommand,
        private readonly InitEnvCommand $initEnvCommand,
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('env:update');
        $this->setDescription('updates environment from template');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectName = getenv('PROJECT_NAME');
        if (!$projectName) {
            $output->writeln("<error>Please execute in project context.</error>");
            return Command::FAILURE;
        }

        $rooterEnvType = getenv('ROOTER_ENV_TYPE');
        if (!$rooterEnvType) {
            $output->writeln("<error>Please set ROOTER_ENV_TYPE in .env before updating.</error>");
            return Command::FAILURE;
        }

        $helper = $this->getHelper('question');
        $output->writeln("This command will update devenv and re-initialise the environment config.");
        $output->writeln("The config files will be overwritten and a backup is created.");
        $question = new ConfirmationQuestion('Are you sure you want to continue? [y/N] ', false);

        $canContinue = $helper->ask($input, $output, $question);

        if (!$canContinue) {
            return Command::SUCCESS;
        }

        $this->stopCommand->run(new ArrayInput([]), $output);

        $this->updateDevenvCommand->run(new ArrayInput([]), $output);

        $this->initEnvCommand->run(new ArrayInput(['type' => $rooterEnvType, '--name' => $projectName]), $output);

        return Command::SUCCESS;
    }

}
