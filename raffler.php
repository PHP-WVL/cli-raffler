#!/usr/bin/env php
<?php
require __DIR__ . '/vendor/autoload.php';

use PhpWvl\Command\RaffleCommand;
use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new RaffleCommand());
$application->run();
