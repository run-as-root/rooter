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
    }

    /**
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rooterHomeBinDir = $this->rooterConfig->getBinDir();
        $output->writeln('==> Creating bin directory');
        if (!is_dir($rooterHomeBinDir) && !mkdir($rooterHomeBinDir, 0755, true) && !is_dir($rooterHomeBinDir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $rooterHomeBinDir));
        }

        $rooterEnvDir = $this->rooterConfig->getEnvironmentsDir();
        $output->writeln('==> Creating environments directory');
        if (!is_dir($rooterEnvDir) && !mkdir($rooterEnvDir, 0755, true) && !is_dir($rooterEnvDir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $rooterEnvDir));
        }

        // Init dnsmasq
        $output->writeln('==> Initialising dnsmasq');
        $this->initDnsmasq->run(new ArrayInput([]), $output);

        // Init traefik
        $output->writeln('==> Initialising traefik');
        $this->initTraefik->run(new ArrayInput([]), $output);

        // Generate ROOT CA and trust ROOT CA
        $this->generateCertificates($output);

        return 0;
    }

    private function generateCertificates(OutputInterface $output): void
    {
        $osType = php_uname('s');
        $rootCaDir = $this->certConfig->getRootCaDir();
        $caKeyPemFile = $this->certConfig->getCaKeyPemFile();
        $caCertPemFile = $this->certConfig->getCaCertPemFile();

        if (!is_dir($rootCaDir)) {
            mkdir("$rootCaDir/certs", 0755, true);
            mkdir("$rootCaDir/crl", 0755, true);
            mkdir("$rootCaDir/newcerts", 0755, true);
            mkdir("$rootCaDir/private", 0700, true);
            touch("$rootCaDir/index.txt");
            file_put_contents("$rootCaDir/serial", '1000');
        }

        if (!file_exists($caKeyPemFile)) {
            $output->writeln('==> Generating private key for local root certificate');
            exec("openssl genrsa -out $caKeyPemFile 2048");
        }

        if (!file_exists($caCertPemFile)) {
            $hostname = gethostname();
            $output->writeln("==> Signing root certificate 'ROOTER Proxy Local CA ('$hostname')'");
            $rootCaConf = $this->rooterConfig->getRooterDir() . "/etc/openssl/rootca.conf";
            $subject = "/C=US/O=rooter.run-as-root.sh/CN=ROOTER Proxy Local CA ($hostname)";
            $command = "openssl req -new -x509 -days 7300 -sha256 -extensions v3_ca -config $rootCaConf -key $caKeyPemFile -out $caCertPemFile -subj \"$subject\"";
            exec($command);
        }

        if (str_starts_with($osType, 'Linux')) {
            if (is_dir('/etc/pki/ca-trust/source/anchors')
                && !file_exists('/etc/pki/ca-trust/source/anchors/rooter-proxy-local-ca.cert.pem')
            ) {
                $output->writeln('==> Trusting root certificate (requires sudo privileges)');
                exec("sudo cp $caCertPemFile /etc/pki/ca-trust/source/anchors/rooter-proxy-local-ca.cert.pem");
                exec('sudo update-ca-trust');
            } elseif (is_dir('/usr/local/share/ca-certificates')
                && !file_exists('/usr/local/share/ca-certificates/rooter-proxy-local-ca.crt')
            ) {
                $output->writeln('==> Trusting root certificate (requires sudo privileges)');
                exec("sudo cp $caCertPemFile /usr/local/share/ca-certificates/rooter-proxy-local-ca.crt");
                exec('sudo update-ca-certificates');
            }
        } elseif (str_starts_with($osType, 'Darwin')) {
            if (!exec('security dump-trust-settings -d | grep "ROOTER Proxy Local CA"')) {
                $output->writeln('==> Trusting root certificate (requires sudo privileges)');
                exec("sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain $caCertPemFile");
            }
        }

        // Generate Certs
        $this->generateCertificateService->generate($this->certConfig->getCertificateName(), $this->certConfig, $output);
    }

}
