#!/usr/bin/env php
<?php
declare(strict_types=1);

namespace RunAsRoot\Rooter;

require_once __DIR__ . '/src/RooterBootstrap.php';

$application = RooterBootstrap::createApplication();
$application->run();
