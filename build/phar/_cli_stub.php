#!/usr/bin/env php
<?php

use RunAsRoot\Rooter\RooterBootstrap;

Phar::mapPhar('rooter.phar');

require 'phar://rooter.phar/src/RooterBootstrap.php';
$application = RooterBootstrap::createApplication();
$application->run();

__HALT_COMPILER();
