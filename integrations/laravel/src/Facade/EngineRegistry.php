<?php

declare(strict_types=1);

/*
 * This file is part of the CMS-IG SEAL project.
 *
 * (c) Alexander Schranz <alexander@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CmsIg\Seal\Integration\Laravel\Facade;

use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\EngineRegistry as SealEngineRegistry;
use Illuminate\Support\Facades\Facade;

/**
 * @method static iterable<string, EngineInterface> getEngines()
 * @method static EngineInterface getEngine(string $name)
 *
 * @see \CmsIg\Seal\EngineRegistry
 */
class EngineRegistry extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SealEngineRegistry::class;
    }
}
