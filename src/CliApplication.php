<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\CompleteCommand;
use Symfony\Component\Console\Command\DumpCompletionCommand;
use Symfony\Component\Console\Command\HelpCommand;

class CliApplication extends BaseApplication
{
    public function __construct(iterable $commands, string $version)
    {
        parent::__construct('rooter', $version);

        foreach ($commands as $command) {
            $this->add($command);
        }

        $this->setDefaultCommand('commands');
    }

    protected function getDefaultCommands(): array
    {
        return [new HelpCommand(), new CompleteCommand(), new DumpCompletionCommand()];
    }
}
