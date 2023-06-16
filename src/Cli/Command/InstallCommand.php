<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command;

use RunAsRoot\Rooter\Cli\Command\Dnsmasq\InitDnsmasqConfigCommand;
use RunAsRoot\Rooter\Cli\Command\Traefik\InitTraefikConfigCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends Command
{
    public function configure()
    {
        $this->setName('install');
        $this->setDescription('Main installation of rooter');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rooterHomeBinDir = ROOTER_HOME_DIR . "/bin";

        if (ROOTER_DIR !== getcwd()) {
            $output->writeln("This command can only be executed in the rooter dir");
            return 1;
        }

        $output->writeln('==> Creating bin directory');
        if (!is_dir($rooterHomeBinDir) && !mkdir($rooterHomeBinDir) && !is_dir($rooterHomeBinDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $rooterHomeBinDir));
        }

        // Init dnsmasq
        $output->writeln('==> Initialising dnsmasq');
        $initDnsmasq = new InitDnsmasqConfigCommand();
        $initDnsmasq->run(new ArrayInput([]), $output);

        // Init traefik
        $output->writeln('==> Initialising traefik');
        $initTraefik = new InitTraefikConfigCommand();
        $initTraefik->run(new ArrayInput([]), $output);

        // Generate ROOT CA and trust ROOT CA
        $rootCaDir = ROOTER_SSL_DIR . "/rootca";
        $caKeyPemFile = "$rootCaDir/private/ca.key.pem";
        $caCertPemFile = "$rootCaDir/certs/ca.cert.pem";

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
            exec('openssl genrsa -out ' . $caKeyPemFile . ' 2048');
        }

        if (!file_exists($caCertPemFile)) {
            $hostname = gethostname();
            $output->writeln("==> Signing root certificate 'ROOTER Proxy Local CA ('$hostname')'");
            $rootCaConf = ROOTER_DIR . "/etc/openssl/rootca.conf";
            $subject = "/C=US/O=rooter.run-as-root.sh/CN=ROOTER Proxy Local CA ($hostname)";
            $command = "openssl req -new -x509 -days 7300 -sha256 -extensions v3_ca -config $rootCaConf -key $caKeyPemFile -out $caCertPemFile -subj \"$subject\"";
            exec($command);
        }

        $osType = php_uname('s');

        if (strpos($osType, 'Linux') === 0) {
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
        } elseif (strpos($osType, 'Darwin') === 0) {
            if (!exec('security dump-trust-settings -d | grep "ROOTER Proxy Local CA"')) {
                $output->writeln('==> Trusting root certificate (requires sudo privileges)');
                exec("sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain $caCertPemFile");
            }
        }

        // Configure resolver for .test domains on macOS
        if (strpos($osType, 'Darwin') === 0) {
            if (!file_exists('/etc/resolver/test')) {
                $output->writeln('==> Configuring resolver for .test domains (requires sudo privileges)');
                if (!is_dir('/etc/resolver')) {
                    exec('sudo mkdir /etc/resolver');
                }
                exec('echo "nameserver 127.0.0.1" | sudo tee /etc/resolver/test >/dev/null');
            }
        } else {
            $output->writeln('<comment>Manual configuration required for Automatic DNS resolution</comment>');
        }

        // Certs
        $certsDir = ROOTER_SSL_DIR . '/certs';
        if (!is_dir($certsDir)) {
            mkdir($certsDir, 0755, true);
        }

        $certificateName = 'rooter.test';

        $certificateKeyPemFile = "$certsDir/$certificateName.key.pem";
        $certificateCsrPemFile = "$certsDir/$certificateName.csr.pem";
        $certificatePemFile = "$certsDir/$certificateName.crt.pem";
        if (file_exists($certificateKeyPemFile)) {
            $output->writeln("<comment>Warning: Certificate for $certificateName already exists! Overwriting...</comment>");
        }

        $certificateSanList = "DNS.1:$certificateName,DNS.2:*.$certificateName";

        $output->writeln("==> Generating private key $certificateName.key.pem");
        exec("openssl genrsa -out $certificateKeyPemFile 2048");

        $output->writeln("==> Generating signing req $certificateName.crt.pem");

        $opensslConfig = file_get_contents("" . (ROOTER_DIR) . "/etc/openssl/certificate.conf");
        $opensslConfig .= "\nextendedKeyUsage = serverAuth,clientAuth\nsubjectAltName = $certificateSanList";
        $subject = "/C=US/O=rooter.run-as-root.sh/CN=$certificateName";

        $tmpConfigFile = tempnam(sys_get_temp_dir(), 'cert_conf');
        file_put_contents($tmpConfigFile, $opensslConfig);

        $command = "openssl req -new -sha256 -config '$tmpConfigFile' -key $certificateKeyPemFile -out $certificateCsrPemFile -subj $subject";
        exec($command);

        $output->writeln("==> Generating certificate $certificateName.crt.pem");
        $command = "openssl x509 -req -days 365 -sha256 -extensions v3_req -extfile $tmpConfigFile -CA $caCertPemFile -CAkey $caKeyPemFile -CAserial $rootCaDir/serial -in $certificateCsrPemFile -out $certificatePemFile";
        exec($command);

        unlink($tmpConfigFile);

        return 0;
    }
}
