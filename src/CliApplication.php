<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\CompleteCommand;
use Symfony\Component\Console\Command\DumpCompletionCommand;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class CliApplication extends BaseApplication
{
    private OutputFormatter $outputFormatter;

    public function __construct(iterable $commands, string $version, OutputFormatter $outputFormatter)
    {
        parent::__construct('rooter', $version);

        foreach ($commands as $command) {
            $this->add($command);
        }

        $this->setDefaultCommand('commands');

        $this->outputFormatter = $outputFormatter;
    }

    public function run(InputInterface $input = null, OutputInterface $output = null): int
    {
        $output ??= new ConsoleOutput(OutputInterface::VERBOSITY_NORMAL, null, $this->outputFormatter);

        return parent::run($input, $output);
    }

    protected function getDefaultCommands(): array
    {
        return [new HelpCommand(), new CompleteCommand(), new DumpCompletionCommand()];
    }
}
