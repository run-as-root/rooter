<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Laravel;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitLaravelNginxCommand extends Command
{
    public function configure()
    {
        $this->setName('laravel:nginx-init');
        $this->setDescription('Initialise nginx config for Laravel');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
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
        ];

        $searchStrings = [];
        $replaceStrings = [];
        foreach ($nginxVarsAllowed as $variable) {
            $value = getenv($variable);
            $searchStrings[] = '${' . $variable . '}';
            $replaceStrings[] = $value;
        }

        // Prepare
        unlink($nginxStateDir . "/nginx.conf");

        if (!is_dir($nginxStateDir . "/tmp")) {
            mkdir($nginxStateDir . "/tmp", 0755, true);
        }

        $nginxTmplDir = getenv("DEVENV_CONFIG_NGINX") ?: ROOTER_DIR . "/environments/laravel/nginx";

        // Read and modify nginx-template.conf
        $nginxTemplate = file_get_contents("$nginxTmplDir/nginx-template.conf");
        $nginxConfig = str_replace($searchStrings, $replaceStrings, $nginxTemplate);

        // Read and modify laravel-template.conf
        $laravelTemplate = file_get_contents("$nginxTmplDir/laravel-template.conf");
        $laravelConfig = str_replace($searchStrings, $replaceStrings, $laravelTemplate);

        // Write configs
        file_put_contents("$nginxStateDir/nginx.conf", $nginxConfig);
        file_put_contents("$nginxStateDir/laravel.conf", $laravelConfig);

        $output->writeln("nginx.conf placed at $nginxStateDir/nginx.conf");
        $output->writeln("laravel.conf placed at $nginxStateDir/laravel.conf");

        return 0;
    }
}
