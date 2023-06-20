<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Env;

use RunAsRoot\Rooter\Config\DevenvConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ShowLogsCommand extends Command
{
    private DevenvConfig $devenvConfig;

    protected function configure()
    {
        $this->setName('env:log');
        $this->setDescription('Show env logs');
        $this->addOption('lines', 'l', InputOption::VALUE_OPTIONAL, 'Number of lines to display');
        $this->addOption('follow', 'f', InputOption::VALUE_NONE, 'follow logs continuously');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->devenvConfig = new DevenvConfig();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lines = $input->getOption('lines');
        $follow = $input->getOption('follow');

        if ($lines && $follow) {
            $output->writeln('You cannot have both lines and follow');
            return 1;
        }

        $flags = ' ';
        $flags .= $follow ? '-f' : '';
        $flags .= $lines ? "-n $lines" : '';

        $command = sprintf('tail %s %s', $flags, $this->devenvConfig->getLogFile());

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(0)->setTty(true)->run();

        return Command::SUCCESS;
    }
}

