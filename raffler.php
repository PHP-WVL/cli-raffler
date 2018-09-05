#!/usr/bin/env php
<?php
require __DIR__ . '/vendor/autoload.php';

use PhpWvl\Command\RaffleCommand;
use Symfony\Component\Console\Application;
use Dotenv\Dotenv;

try {
    (new Dotenv(__DIR__))->load();
} catch (Exception $exception) {
    echo "Could not load .env file, check if your configuration is correct";
    exit();
}

$application = new Application();
$application->add(new RaffleCommand());
$application->run();
