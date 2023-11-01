<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Services;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RestartServicesCommand extends Command
{

    public function __construct(
        private readonly StartServicesCommand $startCommand,
        private readonly StopServicesCommand $stopCommand,
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('services:restart');
        $this->setDescription('restart rooter processes');
    }

    /**
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->stopCommand->run(new ArrayInput([]), $output);

        $this->startCommand->run(new ArrayInput([]), $output);

        return Command::SUCCESS;
    }

}