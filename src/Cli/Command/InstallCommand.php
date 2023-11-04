<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command;

use RunAsRoot\Rooter\Cli\Command\Dnsmasq\InitDnsmasqConfigCommand;
use RunAsRoot\Rooter\Cli\Command\Traefik\InitTraefikConfigCommand;
use RunAsRoot\Rooter\Config\CertConfig;
use RunAsRoot\Rooter\Config\RooterConfig;
use RunAsRoot\Rooter\Service\GenerateCertificateService;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends Command
{
    public function __construct(
        private readonly RooterConfig $rooterConfig,
        private readonly CertConfig $certConfig,
        private readonly InitDnsmasqConfigCommand $initDnsmasq,
        private readonly InitTraefikConfigCommand $initTraefik,
        private GenerateCertificateService $generateCertificateService,
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('install');
        $this->setDescription('Main installation of rooter');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force installation and overwrite existing files');
    }

    /**
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $force = $input->getOption('force');

        $output->writeln('==> Creating bin directory');
        $this->ensureDir($this->rooterConfig->getBinDir());
        $output->writeln('==> Creating environments directory');
        $this->ensureDir($this->rooterConfig->getEnvironmentsDir());

        // Init dnsmasq
        $output->writeln('==> Initialising dnsmasq');
        $this->initDnsmasq->run(new ArrayInput(['--force' => $force]), $output);

        // Init traefik
        $output->writeln('==> Initialising traefik');
        $this->initTraefik->run(new ArrayInput([]), $output);

        // Generate ROOT CA and trust ROOT CA
        $output->writeln('==> Initialising Certificates');
        $this->generateCertificates($output, $force);

        return 0;
    }

    private function generateCertificates(OutputInterface $output, bool $force = false): void
    {
        $osType = php_uname('s');
        $rootCaDir = $this->certConfig->getRootCaDir();
        $caKeyPemFile = $this->certConfig->getCaKeyPemFile();
        $caCertPemFile = $this->certConfig->getCaCertPemFile();

        if ($force === true || !is_dir($rootCaDir)) {
            $this->ensureDir("$rootCaDir/certs");
            $this->ensureDir("$rootCaDir/crl");
            $this->ensureDir("$rootCaDir/newcerts");
            $this->ensureDir("$rootCaDir/private", 0700);
            touch("$rootCaDir/index.txt");
            file_put_contents("$rootCaDir/serial", '1000');
        }

        // Generate ROOT CA
        if ($force === true || !file_exists($caKeyPemFile)) {
            $output->writeln('==> Generating private key for local root certificate');
            $this->execOrFail("openssl genrsa -out $caKeyPemFile 2048");
        }

        // Sign ROOT CA
        if ($force === true || !file_exists($caCertPemFile)) {
            $hostname = gethostname();
            $output->writeln("==> Signing root certificate 'ROOTER Proxy Local CA ('$hostname')'");

            // Create temp file for phar execution
            $tmpRootCaConf = tempnam(sys_get_temp_dir(), 'rooter_rootca_conf');
            file_put_contents($tmpRootCaConf, file_get_contents("{$this->rooterConfig->getRooterDir()}/etc/openssl/rootca.conf"));

            $subject = "/C=US/O=rooter.run-as-root.sh/CN=ROOTER Proxy Local CA ($hostname)";
            $command = "openssl req -new -x509 -days 7300 -sha256 -extensions v3_ca -config $tmpRootCaConf -key $caKeyPemFile -out $caCertPemFile -subj \"$subject\"";
            $this->execOrFail($command);

            unlink($tmpRootCaConf);
        }

        // Trust ROOT CA
        if (str_starts_with($osType, 'Linux')) {
            if (is_dir('/etc/pki/ca-trust/source/anchors')
                && ($force === true || !file_exists('/etc/pki/ca-trust/source/anchors/rooter-proxy-local-ca.cert.pem'))
            ) {
                $output->writeln('==> Trusting root certificate (requires sudo privileges)');
                $this->execOrFail("sudo cp $caCertPemFile /etc/pki/ca-trust/source/anchors/rooter-proxy-local-ca.cert.pem");
                $this->execOrFail('sudo update-ca-trust');
            } elseif (is_dir('/usr/local/share/ca-certificates')
                && ($force === true || !file_exists('/usr/local/share/ca-certificates/rooter-proxy-local-ca.crt'))
            ) {
                $output->writeln('==> Trusting root certificate (requires sudo privileges)');
                $this->execOrFail("sudo cp $caCertPemFile /usr/local/share/ca-certificates/rooter-proxy-local-ca.crt");
                $this->execOrFail('sudo update-ca-certificates');
            }
        } elseif (str_starts_with($osType, 'Darwin')) {
            if ($force === true || !exec('security dump-trust-settings -d | grep "ROOTER Proxy Local CA"')) {
                $output->writeln('==> Trusting root certificate (requires sudo privileges)');
                $this->execOrFail("sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain $caCertPemFile");
            }
        }

        // Generate Certs
        $this->generateCertificateService->generate($this->certConfig->getCertificateName(), $this->certConfig, $output);
    }

    /** @throws RuntimeException */
    private function execOrFail(string $command): void
    {
        $resultCode = null;
        exec(command: $command, result_code: $resultCode);
        if ($resultCode !== 0) {
            throw new \RuntimeException("Failed to execute: '$command'");
        }
    }

    private function ensureDir(string $dirname, int $permissions = 0755): void
    {
        if (!is_dir($dirname) && !mkdir($dirname, $permissions, true) && !is_dir($dirname)
        ) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $dirname));
        }
    }
}
