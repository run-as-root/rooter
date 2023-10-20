<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Traefik;

use RunAsRoot\Rooter\Config\TraefikConfig;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitTraefikConfigCommand extends Command
{
    public function __construct(private readonly TraefikConfig $traefikConfig)
    {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('traefik:config:init');
        $this->setDescription('Initialise rooter traefik configuration for user in $HOME');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->ensureDir($this->traefikConfig->getTraefikHomeDir());
        $this->ensureDir($this->traefikConfig->getConfDir());
        $this->ensureDir($this->traefikConfig->getLogDir());

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

        copy($this->traefikConfig->getEndpointDefault(), "{$this->traefikConfig->getConfDir()}/default.yml");

        return 0;
    }

    private function ensureDir(string $dirname): void
    {
        if (!is_dir($dirname)
            && !mkdir($dirname, 0755, true) && !is_dir($dirname)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $dirname));
        }
    }
}
