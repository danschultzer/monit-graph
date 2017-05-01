<?php

// Enable Composer autoloader
$autoloader = require __DIR__ . '/../vendor/autoload.php';
$autoloader->addPsr4('MonitGraph\Tests\\', __DIR__);
$autoloader->addPsr4('MonitGraph\Tests\Web\\', __DIR__ . '/web');
