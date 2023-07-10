<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter;

use Symfony\Component\Console\Application as BaseApplication;

class CliApplication extends BaseApplication
{
    public function __construct(iterable $commands, string $version)
    {
        parent::__construct('rooter', $version);

        foreach ($commands as $command) {
            $this->add($command);
        }
    }
}
