<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command;

use Symfony\Component\Console\Command\ListCommand;

class ListCommandsCommand extends ListCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('commands');
    }
}