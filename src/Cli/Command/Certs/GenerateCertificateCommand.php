<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Certs;

use RunAsRoot\Rooter\Config\CertConfig;
use RunAsRoot\Rooter\Service\GenerateCertificateService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCertificateCommand extends Command
{
    public function __construct(
        private readonly CertConfig $certConfig,
        private readonly GenerateCertificateService $generateCertificateService,
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('certs:generate');
        $this->setDescription('Generate a cert for a custom domain other than the default rooter.test');
        $this->addArgument('domain', InputArgument::REQUIRED, 'The domain to generate a cert for');
    }

    /**
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // check if root cert is initialised
        if (!file_exists($this->certConfig->getCaCertPemFile())) {
            $output->writeln('Private key for local root certificate is missing.');
            $output->writeln('Please run "rooter install" first.');
            return Command::FAILURE;
        }

        $this->generateCertificateService->generate($input->getArgument('domain'), $this->certConfig, $output);

        return Command::SUCCESS;
    }
}
