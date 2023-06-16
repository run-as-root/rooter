<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends Command
{
    public function configure()
    {
        $this->setName('init');
        $this->setDescription('initialise rooter executables');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (ROOTER_DIR !== getcwd()) {
            $output->writeln("This command can only be executed in the rooter dir");
            return 1;
        }

        $rooterBinDir = ROOTER_HOME_DIR . '/bin';
        if (!is_dir($rooterBinDir)) {
            mkdir($rooterBinDir, 0755, true);
        }

        $bins = ['traefik', 'dnsmasq', 'pv', 'gzip',];
        foreach ($bins as $bin) {
            $this->initBin($bin);
        }

        return 0;
    }

    private function initBin(string $binName): void
    {
        $binTarget = ROOTER_HOME_DIR . "/bin/$binName";

        if (is_file($binTarget)) {
            unlink($binTarget);
        }

        $path = [];
        exec('which ' . $binName, $path);

        $binSource = array_shift($path);

        exec("ln -sf $binSource $binTarget");
    }

}
