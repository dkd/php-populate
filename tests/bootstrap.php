<?php
// Register composer autoloader
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    throw new \RuntimeException(
        'Could not find vendor/autoload.php, make sure you ran composer.'
    );
}

/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__ . '/../vendor/autoload.php';
$loader->addPsr4('Dkd\\Populate\\Tests\\', __DIR__);
