<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Nginx;

use RunAsRoot\Rooter\Cli\Output\EnvironmentsRenderer;
use RunAsRoot\Rooter\Config\RooterConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitNginxCommand extends Command
{
    public function __construct(
        private readonly RooterConfig $rooterConfig,
        private readonly EnvironmentsRenderer $environmentsRenderer
    ) {
        parent::__construct();
    }

    public function configure()
    {
        $this->setName('nginx:init');
        $this->setDescription('Initialise nginx config for a provided environment type');
        $this->addArgument('type', InputArgument::REQUIRED, 'The system you want to initialise');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $envType = $input->getArgument('type');

        $envTmplDir = "{$this->rooterConfig->getEnvironmentTemplatesDir()}/$envType";
        if (!is_dir($envTmplDir)) {
            $output->writeln("<error>unknown environment type: $envType</error>");
            $this->environmentsRenderer->render($input, $output);
            return Command::FAILURE;
        }

        $nginxStateDir = getenv("DEVENV_STATE_NGINX");
        if ($nginxStateDir === false || $nginxStateDir === "") {
            $output->writeln("DEVENV_STATE_NGINX is required");

            return 1;
        }

        $nginxVarsAllowed = [
            'NGINX_DIR_SSL_CERTS',
            'DEVENV_STATE_NGINX',
            'DEVENV_HTTP_PORT',
            'DEVENV_HTTPS_PORT',
            'DEVENV_PHPFPM_SOCKET',
            'DEVENV_ROOT',
            'NGINX_PKG_ROOT',
            'PROJECT_NAME',
            'PROJECT_HOST',
            'HOME',
        ];

        $searchStrings = [];
        $replaceStrings = [];
        foreach ($nginxVarsAllowed as $variable) {
            $value = getenv($variable);
            $searchStrings[] = '${' . $variable . '}';
            $replaceStrings[] = $value;
            $searchStrings[] = '$' . $variable;
            $replaceStrings[] = $value;
        }

        // Prepare
        $nginxStateConf = $nginxStateDir . "/nginx.conf";
        if (is_file($nginxStateConf)) {
            unlink($nginxStateConf);
        }

        $nginxTmpDir = $nginxStateDir . "/tmp";
        if (!is_dir($nginxTmpDir) && !mkdir($nginxTmpDir, 0755, true) && !is_dir($nginxTmpDir)) {
            throw new \RuntimeException("Directory '$nginxTmpDir' was not created");
        }

        $nginxTmplDir = getenv("DEVENV_CONFIG_NGINX") ?: $this->rooterConfig->getEnvironmentTemplatesDir() . "/$envType/nginx";

        // Read and modify nginx-template.conf
        $nginxTemplate = file_get_contents("$nginxTmplDir/nginx-template.conf");
        $nginxConfig = str_replace($searchStrings, $replaceStrings, $nginxTemplate);

        // Read and modify $type-template.conf
        $envTypeTemplate = file_get_contents("$nginxTmplDir/$envType-template.conf");
        $envTypeConfig = str_replace($searchStrings, $replaceStrings, $envTypeTemplate);

        // Write configs
        file_put_contents("$nginxStateDir/nginx.conf", $nginxConfig);
        file_put_contents("$nginxStateDir/$envType.conf", $envTypeConfig);

        $output->writeln("nginx.conf placed at $nginxStateDir/nginx.conf");
        $output->writeln("$envType.conf placed at $nginxStateDir/$envType.conf");

        return 0;
    }
}
