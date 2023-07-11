<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class RooterBootstrap
{
    /** @throws \Exception */
    public static function createApplication(): CliApplication
    {
        $baseDir = dirname(__DIR__);

        self::initAutoload($baseDir);

        $rooterEnvDir = self::getRooterProjectRoot($baseDir);

        \define('ROOTER_DIR', $baseDir);
        \define('ROOTER_HOME_DIR', getenv("HOME") . "/.rooter");
        \define('ROOTER_SSL_DIR', ROOTER_HOME_DIR . "/ssl");
        \define('ROOTER_PROJECT_ROOT', $rooterEnvDir);
        \define('ROOTER_PROJECT_DIR', ROOTER_PROJECT_ROOT . "/.rooter");

        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator($baseDir));
        $loader->load('config/services.yaml');

        $container->compile();

        return $container->get(CliApplication::class);
    }

    private static function getRooterProjectRoot(string $rooterDir): string
    {
        $rooterEnvDir = (string)getcwd();
        if ($rooterEnvDir === $rooterDir) {
            return $rooterEnvDir;
        }

        $isRooterDir = false;
        while (!$isRooterDir) {
            $isRooterDir = file_exists("$rooterEnvDir/devenv.nix");
            if (!$isRooterDir) {
                $rooterEnvDir = dirname($rooterEnvDir);
            }
        }
        return $rooterEnvDir;
    }

    /** @throws \ErrorException */
    private static function initAutoload(string $baseDir): void
    {
        $autoloadDirs = [
            $baseDir . '/../../autoload.php',
            $baseDir . '/../vendor/autoload.php',
            $baseDir . '/vendor/autoload.php',
        ];
        $rooterComposerInstall = null;
        foreach ($autoloadDirs as $file) {
            if (file_exists($file)) {
                $rooterComposerInstall = $file;
                break;
            }
        }

        if ($rooterComposerInstall === null) {
            throw new \ErrorException(
                'You need to set up the project dependencies using Composer:' . PHP_EOL . PHP_EOL .
                '    composer install' . PHP_EOL . PHP_EOL .
                'After that for a first time installation run.' . PHP_EOL . PHP_EOL .
                '    rooter install' . PHP_EOL . PHP_EOL
            );
        }
        require $rooterComposerInstall;
    }
}
