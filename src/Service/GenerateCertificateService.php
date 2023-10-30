<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Service;

use RunAsRoot\Rooter\Config\CertConfig;
use RunAsRoot\Rooter\Config\RooterConfig;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCertificateService
{

    public function __construct(private readonly RooterConfig $rooterConfig)
    {
    }

    public function generate(string $certificateName, CertConfig $certConfig, OutputInterface $output): void
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
            $output->writeln("<comment>Warning: Certificate for $certificateName already exists! Overwriting...</comment>");
        }

        $certificateSanList = "DNS.1:$certificateName,DNS.2:*.$certificateName";

        $output->writeln("==> Generating private key $certificateName.key.pem");
        exec("openssl genrsa -out $certificateKeyPemFile 2048");

        $output->writeln("==> Generating signing req $certificateName.crt.pem");

        $opensslCertificateConf = $this->rooterConfig->getRooterDir() . "/etc/openssl/certificate.conf";
        $opensslConfig = file_get_contents($opensslCertificateConf);
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
    }
}
