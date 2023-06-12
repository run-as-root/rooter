<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Traefik;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RegisterTraefikConfigCommand extends Command
{
    public function configure()
    {
        $this->setName('traefik:config:register');
        $this->setDescription('Register a project specific traefik config');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectName = $_ENV['PROJECT_NAME']; # @todo check if set

        $sourceFile = ROOTER_DIR . '/etc/traefik/conf.d/endpoint-tmpl.yml';
        $targetFile = ROOTER_HOME_DIR . "/traefik/conf.d/$projectName.yml";

        $tmplVars = array_merge(
            $_ENV,
            [
                'ROOTER_DIR' => ROOTER_DIR,
                'ROOTER_HOME_DIR' => ROOTER_HOME_DIR,
            ]
        );
        $sourceContent = file_get_contents($sourceFile);

        $traefikYml = preg_replace_callback(
            '/\${(.*?)}/',
            static function ($matches) use ($tmplVars) {
                $varName = $matches[1];

                return $tmplVars[$varName] ?? '';
            },
            $sourceContent
        );

        file_put_contents($targetFile, $traefikYml);

        $output->writeln("Registered traefik configuration for $projectName");
        $output->writeln('----------');
        $output->writeln(file_get_contents($targetFile));

        return 0;
    }
}
