<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

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