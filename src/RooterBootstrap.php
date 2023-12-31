<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag;

class RooterBootstrap
{
    /** @throws \Exception */
    public static function createApplication(): CliApplication
    {
        $baseDir = dirname(__DIR__);

        self::initAutoload($baseDir);

        $rooterEnvDir = self::getRooterProjectRoot($baseDir);

        $parameterBag = new EnvPlaceholderParameterBag([
            'rooter.dir' => $baseDir,
            'rooter.home_dir' => getenv("HOME") . "/.rooter",
            'rooter.environment_root' => $rooterEnvDir,
        ]);

        $container = new ContainerBuilder($parameterBag);
        $loader = new YamlFileLoader($container, new FileLocator($baseDir));
        $loader->load('config/services.yaml');

        $container->compile(true);

        return $container->get(CliApplication::class);
    }

    private static function getRooterProjectRoot(string $rooterDir): string
    {
        $cwd = (string)getcwd();
        if ($cwd === $rooterDir) {
            return $cwd;
        }

        $isRooterDir = false;
        $rooterEnvDir = $cwd;
        while (!$isRooterDir) {
            $isRooterDir = file_exists("$rooterEnvDir/devenv.nix");
            if (!$isRooterDir) {
                $rooterEnvDir = dirname($rooterEnvDir);
            }
            if ($rooterEnvDir === dirname($rooterEnvDir)) {
                // We have reached top-level and could not find an environment
                // We set the project dir to current working dir
                $rooterEnvDir = $cwd;
                break;
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
