<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Config;

class CertConfig
{
    private string $rootCaDir = ROOTER_SSL_DIR . "/rootca";
    private string $certsDir = ROOTER_SSL_DIR . "/certs";
    private string $caKeyPemFile = "private/ca.key.pem";
    private string $caCertPemFile = "certs/ca.cert.pem";
    private string $certificateName = 'rooter.test';

    public function getRootCaDir(): string
    {
        return $this->rootCaDir;
    }

    public function getCaKeyPemFile(): string
    {
        return $this->rootCaDir . '/' . $this->caKeyPemFile;
    }

    public function getCaCertPemFile(): string
    {
        return  $this->rootCaDir . '/' .$this->caCertPemFile;
    }

    public function getCertificateName(): string
    {
        return $this->certificateName;
    }

    public function getCertsDir(): string
    {
        return $this->certsDir;
    }

}
