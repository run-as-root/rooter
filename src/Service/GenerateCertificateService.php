<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Service;

use RunAsRoot\Rooter\Config\CertConfig;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCertificateService
{

    public function __construct(private readonly OutputInterface $output)
    {
    }

    public function generate(string $certificateName, CertConfig $certConfig): void
    {
        $rootCaDir = $certConfig->getRootCaDir();
        $caKeyPemFile = $certConfig->getCaKeyPemFile();
        $caCertPemFile = $certConfig->getCaCertPemFile();
        $certsDir = $certConfig->getCertsDir();

        if (!is_dir($certsDir)) {
            mkdir($certsDir, 0755, true);
        }

        $certificateKeyPemFile = "$certsDir/$certificateName.key.pem";
        $certificateCsrPemFile = "$certsDir/$certificateName.csr.pem";
        $certificatePemFile = "$certsDir/$certificateName.crt.pem";
        if (file_exists($certificateKeyPemFile)) {
            $this->output->writeln("<comment>Warning: Certificate for $certificateName already exists! Overwriting...</comment>");
        }

        $certificateSanList = "DNS.1:$certificateName,DNS.2:*.$certificateName";

        $this->output->writeln("==> Generating private key $certificateName.key.pem");
        exec("openssl genrsa -out $certificateKeyPemFile 2048");

        $this->output->writeln("==> Generating signing req $certificateName.crt.pem");

        $opensslConfig = file_get_contents(ROOTER_DIR . "/etc/openssl/certificate.conf");
        $opensslConfig .= "\nextendedKeyUsage = serverAuth,clientAuth\nsubjectAltName = $certificateSanList";
        $subject = "/C=US/O=rooter.run-as-root.sh/CN=$certificateName";

        $tmpConfigFile = tempnam(sys_get_temp_dir(), 'cert_conf');
        file_put_contents($tmpConfigFile, $opensslConfig);

        $command = "openssl req -new -sha256 -config '$tmpConfigFile' -key $certificateKeyPemFile -out $certificateCsrPemFile -subj $subject";
        exec($command);

        $this->output->writeln("==> Generating certificate $certificateName.crt.pem");
        $command = "openssl x509 -req -days 365 -sha256 -extensions v3_req -extfile $tmpConfigFile -CA $caCertPemFile -CAkey $caKeyPemFile -CAserial $rootCaDir/serial -in $certificateCsrPemFile -out $certificatePemFile";
        exec($command);

        unlink($tmpConfigFile);
    }
}
