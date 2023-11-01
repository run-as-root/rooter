<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Config;

class CertConfig
{
    public function __construct(
        private readonly string $rootCaDir,
        private readonly string $certsDir,
        private ?string $caKeyPemFile = null,
        private ?string $caCertPemFile = null,
        private ?string $certificateName = null,
    ) {
        $this->caKeyPemFile = $caKeyPemFile ?? "$rootCaDir/private/ca.key.pem";
        $this->caCertPemFile = $caCertPemFile ?? "$rootCaDir/certs/ca.cert.pem";
        $this->certificateName = $certificateName ?? 'rooter.test';
    }

    public function getRootCaDir(): string
    {
        return $this->rootCaDir;
    }

    public function getCaKeyPemFile(): string
    {
        return $this->caKeyPemFile;
    }

    public function getCaCertPemFile(): string
    {
        return $this->caCertPemFile;
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
