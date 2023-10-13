<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command;

use RunAsRoot\Rooter\Config\RooterConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @deprecated env variables for ROOTER_TRAEFIK_BIN and ROOTER_%s_BIN are being used instead
 */
class InitCommand extends Command
{
    public function __construct(private readonly RooterConfig $rooterConfig)
    {
        parent::__construct();
    }

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

        $rooterBinDir = $this->rooterConfig->getBinDir();
        if (!is_dir($rooterBinDir)
            && !mkdir($rooterBinDir, 0755, true) && !is_dir($rooterBinDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $rooterBinDir));
        }

        $bins = ['traefik', 'dnsmasq', 'pv', 'gzip',];
        foreach ($bins as $bin) {
            try {
                $this->initBin($bin);
                $output->writeln("$bin initialised");
            } catch (\Exception $e) {
                $output->writeln("<error>$bin initialisation error: {$e->getMessage()}</error>");
            }
        }

        return 0;
    }

    /**
     * @throws \RuntimeException
     */
    private function initBin(string $binName): void
    {
        $binSource = getenv(sprintf("ROOTER_%s_BIN", strtoupper($binName)));

        if (empty($binSource)) {
            $path = [];
            exec('which ' . $binName, $path);

            if (count($path) === 0) {
                throw new \RuntimeException("Could not find $binName in PATH");
            }

            $binSource = array_shift($path);
        }

        // re-create symlink
        $binTarget = "{$this->rooterConfig->getBinDir()}/$binName";
        if (is_file($binTarget)) {
            unlink($binTarget);
        }

        exec("ln -sf $binSource $binTarget");
    }

}
