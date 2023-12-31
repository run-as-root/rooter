<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Traefik;

use RunAsRoot\Rooter\Config\RooterConfig;
use RunAsRoot\Rooter\Config\TraefikConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Environment as TwigEnvironment;

class RegisterTraefikConfigCommand extends Command
{

    public function __construct(
        private readonly TraefikConfig $traefikConfig,
        private readonly TwigEnvironment $twig,
        private readonly RooterConfig $rooterConfig
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('traefik:config:register');
        $this->setDescription('Register a project specific traefik config');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectName = (string)getenv('PROJECT_NAME');

        if (empty($projectName)) {
            $output->writeln("<error>PROJECT_NAME is not set. This command should be executed in a project context.</error>");
            return Command::FAILURE;
        }

        $hasHttp = !empty(getenv('DEVENV_HTTP_PORT'));
        $hasHttps = !empty(getenv('DEVENV_HTTPS_PORT'));
        $hasMail = !empty(getenv('DEVENV_MAIL_UI_PORT'));
        $hasAmqp = !empty(getenv('DEVENV_AMQP_MANAGEMENT_PORT'));
        $hasTldDomains = !empty(getenv('PROJECT_TLD'));

        if (!$hasHttp && !$hasHttps && !$hasAmqp) {
            $output->writeln("<info>No ports configured. Traefik config not generated</info>");
            return Command::SUCCESS;
        }

        $fqdnRooterLocal = sprintf("%s.%s", $projectName, "rooter.test");

        $tmplVars = array_merge(
            $_ENV,
            [
                'ROOTER_DIR' => $this->rooterConfig->getRooterDir(),
                'ROOTER_HOME_DIR' => $this->rooterConfig->getRooterHomeDir(),
                'ROOTER_PROJECT_HOST' => $fqdnRooterLocal,
                'TRAEFIK_HTTP_RULE' => $this->getTraefikHttpRule($projectName),
                'hasHttp' => $hasHttp,
                'hasHttps' => $hasHttps,
                'hasMail' => $hasMail,
                'hasAmqp' => $hasAmqp,
                'hasTldDomains' => $hasTldDomains,
            ]
        );

        $traefikYml = $this->twig->render($this->traefikConfig->getEndpointTmpl(), $tmplVars);

        $targetFile = $this->traefikConfig->getEndpointConfPath($projectName);

        file_put_contents($targetFile, $traefikYml);

        $output->writeln("traefik configuration registered for $projectName");

        if ($output->isVerbose()) {
            $output->writeln('----------');
            $output->writeln(file_get_contents($targetFile));
        }

        return 0;
    }

    private function getTraefikHttpRule(string $projectName): string
    {
        $projectTld = getenv('PROJECT_TLD');
        $localDomain = "rooter.test";

        $envDomain = sprintf("%s.%s", $projectName, $localDomain);

        $traefikHttpRule = "Host(`$envDomain`) || HostRegexp(`{subdomain:.+}.$envDomain`)";
        if (is_string($projectTld)) {
            $traefikHttpRule .= " || Host(`$projectTld`) || HostRegexp(`{subdomain:.+}.$projectTld`)";
        }

        $subdomainSlugs = getenv('DEVENV_HTTP_SUBDOMAINS');

        if (empty($subdomainSlugs)) {
            return $traefikHttpRule;
        }

        $subdomainSlugList = explode(',', $subdomainSlugs);
        $subdomains = '';
        foreach ($subdomainSlugList as $subdomainSlug) {
            $subdomain = sprintf("%s.%s", $subdomainSlug, $localDomain);
            $subdomains .= "`$subdomain`,";
        }
        if (!empty($subdomains)) {
            $traefikHttpRule .= " || Host($subdomains)";
        }

        return $traefikHttpRule;
    }
}
