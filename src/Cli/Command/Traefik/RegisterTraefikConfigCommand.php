<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Traefik;

use RunAsRoot\Rooter\Config\TraefikConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RegisterTraefikConfigCommand extends Command
{
    private const ROOTER_DOMAIN_TMPL = "%s.rooter.test";

    public function __construct(private readonly TraefikConfig $traefikConfig)
    {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('traefik:config:register');
        $this->setDescription('Register a project specific traefik config');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectName = (string) getenv('PROJECT_NAME');

        if (empty($projectName)) {
            $output->writeln("<error>PROJECT_NAME is not set. This command should be executed in a project context.</error>");
            return Command::FAILURE;
        }

        $traefikHttpRule = $this->getTraefikHttpRule($projectName);

        $tmplVars = array_merge(
            $_ENV,
            [
                'ROOTER_DIR' => ROOTER_DIR,
                'ROOTER_HOME_DIR' => ROOTER_HOME_DIR,
                'TRAEFIK_HTTP_RULE'=> $traefikHttpRule,
            ]
        );
        $sourceContent = file_get_contents($this->traefikConfig->getEndpointTmpl());

        $traefikYml = preg_replace_callback(
            '/\${(.*?)}/',
            static function ($matches) use ($tmplVars) {
                $varName = $matches[1];

                return $tmplVars[$varName] ?? '';
            },
            $sourceContent
        );

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
        $envDomain = sprintf(self::ROOTER_DOMAIN_TMPL, $projectName);

        $traefikHttpRule = "Host(`$envDomain`) || HostRegexp(`{subdomain:.+}.$envDomain`)";

        $subdomainSlugs = getenv('DEVENV_HTTP_SUBDOMAINS');

        if (empty($subdomainSlugs)) {
            return $traefikHttpRule;
        }

        $subdomainSlugList = explode(',', $subdomainSlugs);
        $subdomains = '';
        foreach ($subdomainSlugList as $subdomainSlug) {
            $subdomain = sprintf(self::ROOTER_DOMAIN_TMPL, $subdomainSlug);
            $subdomains .= "`$subdomain`,";
        }
        if (!empty($subdomains)) {
            $traefikHttpRule .= " || Host($subdomains)";
        }

        return $traefikHttpRule;
    }
}
