<?php

declare(strict_types=1);

use App\Environment;
use Dotenv\Dotenv;

require_once \dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv::createUnsafeMutable(\dirname(__DIR__));
$dotenv->load();

Environment::prepare();
