<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Output;

use RunAsRoot\Rooter\Config\RooterConfig;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EnvironmentsRenderer
{
    public function __construct(private RooterConfig $rooterConfig)
    {
    }

    public function render(InputInterface $input, OutputInterface $output): void
    {
        $availableEnvTmpls = scandir($this->rooterConfig->getEnvironmentTemplatesDir());

        $types = [];
        foreach ($availableEnvTmpls as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $types[] = $name;
        }

        $output->writeln("Available environments:");
        $io = new SymfonyStyle($input, $output);
        $io->listing($types);
    }
}
