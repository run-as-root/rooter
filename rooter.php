#!/usr/bin/env php
<?php

namespace RunAsRoot\Rooter;

use RunAsRoot\Rooter\Cli\CommandList;
use Symfony\Component\Console\Application;

$autoloadDirs = [
    __DIR__ . '/../../autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
];
foreach ($autoloadDirs as $file) {
    if (file_exists($file)) {
        \define('ROOTER_COMPOSER_INSTALL', $file);

        break;
    }
}

unset($file, $autoloadDirs);

if (!\defined('ROOTER_COMPOSER_INSTALL')) {
    fwrite(
        STDERR,
        'You need to set up the project dependencies using Composer:' . PHP_EOL . PHP_EOL .
        '    composer install' . PHP_EOL . PHP_EOL .
        'You can learn all about Composer on https://getcomposer.org/.' . PHP_EOL
    );

    die(1);
}

\define('ROOTER_DIR', __DIR__);
\define('ROOTER_HOME_DIR', getenv("HOME") . "/.rooter");
\define('ROOTER_SSL_DIR', ROOTER_HOME_DIR . "/ssl");
\define('ROOTER_PROJECT_ROOT', getcwd());
\define('ROOTER_PROJECT_DIR', ROOTER_PROJECT_ROOT . "/.rooter");

require ROOTER_COMPOSER_INSTALL;

$commands = new CommandList();

$application = new Application();
$application->addCommands($commands->getCommands());
$application->run();
