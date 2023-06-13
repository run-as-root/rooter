<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter\Cli\Command\Shopware6;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitShopware6NginxCommand extends Command
{
    public function configure()
    {
        $this->setName('shopware6:nginx-init');
        $this->setDescription('Initialise nginx config for Shopware6');
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

        $nginxTmplDir = getenv("DEVENV_CONFIG_NGINX") ?: ROOTER_DIR . "/environments/shopware6/nginx";

        // Read and modify nginx-template.conf
        $nginxTemplate = file_get_contents("$nginxTmplDir/nginx-template.conf");
        $nginxConfig = str_replace($searchStrings, $replaceStrings, $nginxTemplate);

        // Read and modify shopware6-template.conf
        $magento2Template = file_get_contents("$nginxTmplDir/shopware6-template.conf");
        $magento2Config = str_replace($searchStrings, $replaceStrings, $magento2Template);

        // Write configs
        file_put_contents("$nginxStateDir/nginx.conf", $nginxConfig);
        file_put_contents("$nginxStateDir/shopware6.conf", $magento2Config);

        $output->writeln("nginx.conf placed at $nginxStateDir/nginx.conf");
        $output->writeln("shopware6.conf placed at $nginxStateDir/shopware6.conf");

        return 0;
    }
}
