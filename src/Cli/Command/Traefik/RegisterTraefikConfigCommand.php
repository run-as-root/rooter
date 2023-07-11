<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Traefik;

use RunAsRoot\Rooter\Config\TraefikConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RegisterTraefikConfigCommand extends Command
{
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
        $projectName = getenv('PROJECT_NAME');

        if (empty($projectName)) {
            $output->writeln("<error>PROJECT_NAME is not set. This command should be executed in a project context.</error>");
            return Command::FAILURE;
        }

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

        $output->writeln("traefik configuration registered for $projectName");

        if ($output->isVerbose()) {
            $output->writeln('----------');
            $output->writeln(file_get_contents($targetFile));
        }

        return 0;
    }
}
