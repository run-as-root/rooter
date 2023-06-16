<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Traefik;

use RunAsRoot\Rooter\Config\TraefikConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitTraefikConfigCommand extends Command
{
    private TraefikConfig $traefikConfig;

    public function configure()
    {
        $this->setName('traefik:config:init');
        $this->setDescription('Initialise rooter traefik configuration for user in $HOME');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->traefikConfig = new TraefikConfig();
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $traefikConfDir = $this->traefikConfig->getTraefikHomeDir();
        if (!is_dir($traefikConfDir)) {
            mkdir($traefikConfDir, 0755, true);
        }

        $confDir = $this->traefikConfig->getConfDir();
        if (!is_dir($confDir)) {
            mkdir($confDir, 0755, true);
        }

        $logDir = $this->traefikConfig->getLogDir();
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $traefikConf = $this->traefikConfig->getTraefikConf();
        if (file_exists($traefikConf)) {
            unlink($traefikConf);
        }

        $tmplVars = array_merge(
            $_ENV,
            ['ROOTER_HOME_DIR' => ROOTER_HOME_DIR]
        );

        $traefikTmpl = file_get_contents($this->traefikConfig->getConfTmpl());

        $traefikYml = preg_replace_callback(
            '/\${(.*?)}/',
            static function ($matches) use ($tmplVars) {
                $varName = $matches[1];

                return $tmplVars[$varName] ?? '';
            },
            $traefikTmpl
        );

        file_put_contents($traefikConf, $traefikYml);

        copy($this->traefikConfig->getEndpointDefault(), "$confDir/default.yml");

        return 0;
    }
}
