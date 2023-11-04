<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Dnsmasq;

use RunAsRoot\Rooter\Config\DnsmasqConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InitDnsmasqConfigCommand extends Command
{
    public function __construct(private readonly DnsmasqConfig $dnsmasqConfig)
    {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('dnsmasq:config:init');
        $this->setDescription('Initialise rooter dnsmasq configuration for user in $HOME');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force installation and overwrite existing files');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $force = $input->getOption('force');
        $this->ensureDir($this->dnsmasqConfig->getHomeDir());
        $this->ensureDir($this->dnsmasqConfig->getLogDir());

        $dnsmasqConf = $this->dnsmasqConfig->getDnsmasqConf();
        if (file_exists($dnsmasqConf)) {
            unlink($dnsmasqConf);
        }

        # @todo allow port adjustment?
        copy($this->dnsmasqConfig->getConfTmpl(), $dnsmasqConf);

        // Initialise resolver config for .test and rooter.test
        $this->initialiseResolverConf($output, $force);

        return 0;
    }

    private function initialiseResolverConf(OutputInterface $output, bool $force = false): void
    {
        $resolverConfTmpl = $this->dnsmasqConfig->getResolverTmpl();
        $resolverConf = $this->dnsmasqConfig->getResolverConf();

        $osType = php_uname('s');
        // Configure resolver for .test domains on macOS
        if (!str_starts_with($osType, 'Darwin')) {
            $output->writeln('<comment>Manual configuration required for Automatic DNS resolution</comment>');
            return;
        }

        if (!is_dir('/etc/resolver')) {
            $output->writeln('==> Configuring resolver (requires sudo privileges)');
            $this->execOrFail('sudo mkdir /etc/resolver');
        }

        // Create temp file for phar execution
        $tmpResolverConf = tempnam(sys_get_temp_dir(), 'rooter_resolver_conf');
        file_put_contents($tmpResolverConf, file_get_contents($resolverConfTmpl));

        // Place the rooter.test resolver config
        if ($force === true || !file_exists($resolverConf)) {
            $output->writeln('==> Configuring resolver for rooter.test domains (requires sudo privileges)');
            $this->execOrFail("sudo cp $tmpResolverConf $resolverConf");
        }

        // Place the test resolver config
        $resolverConfTest = '/etc/resolver/test';
        if ($force === true || !file_exists($resolverConfTest)) {
            $output->writeln('==> Configuring resolver for .test domains (requires sudo privileges)');
            $this->execOrFail("sudo cp $tmpResolverConf $resolverConfTest");
        }

        unlink($tmpResolverConf);
    }

    /** @throws \RuntimeException */
    private function execOrFail(string $command): void
    {
        $resultCode = null;
        exec(command: $command, result_code: $resultCode);
        if ($resultCode !== 0) {
            throw new \RuntimeException("Failed to execute: '$command'");
        }
    }

    private function ensureDir(string $dirname): void
    {
        if (!is_dir($dirname) && !mkdir($dirname, 0755, true) && !is_dir($dirname)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dirname));
        }
    }
}
