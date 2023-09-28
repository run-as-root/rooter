<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Certs;

use RunAsRoot\Rooter\Config\CertConfig;
use RunAsRoot\Rooter\Config\RooterConfig;
use RunAsRoot\Rooter\Service\GenerateCertificateService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCertificateCommand extends Command
{
    private GenerateCertificateService $generateCertificateService;

    public function __construct(
        private readonly RooterConfig $rooterConfig,
        private readonly CertConfig $certConfig,
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('certs:generate');
        $this->setDescription('Generate a cert for a custom domain other than the default rooter.test');
        $this->addArgument('domain', InputArgument::REQUIRED, 'The domain to generate a cert for');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->generateCertificateService = new GenerateCertificateService($output);
    }

    /**
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (ROOTER_DIR !== getcwd()) {
            $output->writeln("This command can only be executed in the rooter dir");
            return 1;
        }

        // check if root cert is initalised
        if (!file_exists($this->certConfig->getCaCertPemFile())) {
            $output->writeln('Private key for local root certificate is missing.');
            $output->writeln('Please run "rooter install" first.');
            return Command::FAILURE;
        }

        $this->generateCertificateService->generate($input->getArgument('domain'), $this->certConfig);

        return Command::SUCCESS;
    }
}
