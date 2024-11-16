<?php

declare(strict_types=1);

namespace App\Application;

use CmsIg\Seal\Integration\Spiral\Bootloader\SealBootloader;
use Spiral\Boot\Bootloader\CoreBootloader;
use Spiral\Bootloader as Framework;
use Spiral\Bootloader\Views\TranslatedCacheBootloader;
use Spiral\Cache\Bootloader\CacheBootloader;
use Spiral\DotEnv\Bootloader\DotenvBootloader;
use Spiral\Events\Bootloader\EventsBootloader;
use Spiral\League\Event\Bootloader\EventBootloader;
use Spiral\Monolog\Bootloader\MonologBootloader;
use Spiral\Nyholm\Bootloader\NyholmBootloader;
use Spiral\Prototype\Bootloader\PrototypeBootloader;
use Spiral\RoadRunnerBridge\Bootloader as RoadRunnerBridge;
use Spiral\Sapi\Bootloader\SapiBootloader;
use Spiral\Scaffolder\Bootloader\ScaffolderBootloader;
use Spiral\Stempler\Bootloader\StemplerBootloader;
use Spiral\Tokenizer\Bootloader\TokenizerListenerBootloader;
use Spiral\Views\Bootloader\ViewsBootloader;
use Spiral\YiiErrorHandler\Bootloader\YiiErrorHandlerBootloader;

class Kernel extends \Spiral\Framework\Kernel
{
    protected const SYSTEM = [
        CoreBootloader::class,
        TokenizerListenerBootloader::class,
        DotenvBootloader::class,
    ];

    protected const LOAD = [
        // Logging and exceptions handling
        MonologBootloader::class,
        YiiErrorHandlerBootloader::class,
        Bootloader\ExceptionHandlerBootloader::class,

        // Application specific logs
        Bootloader\LoggingBootloader::class,

        // RoadRunner
        RoadRunnerBridge\LoggerBootloader::class,
        RoadRunnerBridge\HttpBootloader::class,
        RoadRunnerBridge\CacheBootloader::class,

        // Core Services
        Framework\SnapshotsBootloader::class,

        // Security and validation
        Framework\Security\EncrypterBootloader::class,
        Framework\Security\FiltersBootloader::class,
        Framework\Security\GuardBootloader::class,

        // HTTP extensions
        Framework\Http\RouterBootloader::class,
        Framework\Http\JsonPayloadsBootloader::class,
        Framework\Http\CookiesBootloader::class,
        Framework\Http\SessionBootloader::class,
        Framework\Http\CsrfBootloader::class,
        Framework\Http\PaginationBootloader::class,

        // Event Dispatcher
        EventsBootloader::class,
        EventBootloader::class,

        // Views and view translation
        ViewsBootloader::class,
        TranslatedCacheBootloader::class,
        StemplerBootloader::class,

        // Cache
        CacheBootloader::class,

        SapiBootloader::class,

        NyholmBootloader::class,

        // Console commands
        Framework\CommandBootloader::class,
        RoadRunnerBridge\CommandBootloader::class,
        ScaffolderBootloader::class,

        // Configure route groups, middleware for route groups
        Bootloader\RoutesBootloader::class,

        // Fast code prototyping
        PrototypeBootloader::class,

        // Custom
        SealBootloader::class,
    ];

    protected const APP = [];
}
