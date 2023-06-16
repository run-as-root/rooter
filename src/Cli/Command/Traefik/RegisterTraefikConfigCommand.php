<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Traefik;

use RunAsRoot\Rooter\Config\TraefikConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RegisterTraefikConfigCommand extends Command
{
    private TraefikConfig $traefikConfig;

    public function configure()
    {
        $this->setName('traefik:config:register');
        $this->setDescription('Register a project specific traefik config');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->traefikConfig = new TraefikConfig();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectName = $_ENV['PROJECT_NAME']; # @todo check if set

        $tmplVars = array_merge(
            $_ENV,
            [
                'ROOTER_DIR' => ROOTER_DIR,
                'ROOTER_HOME_DIR' => ROOTER_HOME_DIR,
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

        $output->writeln("Registered traefik configuration for $projectName");
        $output->writeln('----------');
        $output->writeln(file_get_contents($targetFile));

        return 0;
    }
}
