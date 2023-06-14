<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Traefik;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitTraefikConfigCommand extends Command
{
    public function configure()
    {
        $this->setName('traefik:config:init');
        $this->setDescription('Initialise rooter traefik configuration for user in $HOME');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $traefikConfDir = ROOTER_HOME_DIR . '/traefik';
        $traefikConf = $traefikConfDir . '/traefik.yml';

        if (!is_dir($traefikConfDir)) {
            mkdir($traefikConfDir, 0755, true);
        }
        if (!is_dir("$traefikConfDir/conf.d/")) {
            mkdir("$traefikConfDir/conf.d/", 0755, true);
        }
        if (!is_dir("$traefikConfDir/logs/")) {
            mkdir("$traefikConfDir/logs/", 0755, true);
        }

        if (file_exists($traefikConf)) {
            unlink($traefikConf);
        }

        $tmplVars = array_merge(
            $_ENV,
            ['ROOTER_HOME_DIR' => ROOTER_HOME_DIR]
        );

        $traefikTmpl = file_get_contents(ROOTER_DIR . '/etc/traefik.yml');

        $traefikYml = preg_replace_callback(
            '/\${(.*?)}/',
            static function ($matches) use ($tmplVars) {
                $varName = $matches[1];

                return $tmplVars[$varName] ?? '';
            },
            $traefikTmpl
        );

        file_put_contents($traefikConf, $traefikYml);

        copy(ROOTER_DIR . '/etc/traefik/conf.d/default.yml', "$traefikConfDir/conf.d/default.yml");

        return 0;
    }
}
