<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RestartEnvCommand extends Command
{
    public function __construct(
        private readonly StartCommand $startEnvCommand,
        private readonly StopCommand $stopEnvCommand
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('restart');
        $this->setAliases(['env:restart']);
        $this->setDescription('restart environment processes');
        $this->addOption('debug', '', InputOption::VALUE_NONE, 'activate debug mode');
    }

    /**
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $debug = $input->getOption('debug');

        $this->stopEnvCommand->run(new ArrayInput([]), $output);

        $this->startEnvCommand->run(new ArrayInput(['--debug' => $debug]), $output);

        return Command::SUCCESS;
    }

}
