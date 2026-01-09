<?php

declare(strict_types=1);

use Dotenv\Dotenv;

require_once \dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv::createUnsafeMutable(\dirname(__DIR__));
$dotenv->load();

App\Environment::prepare();
