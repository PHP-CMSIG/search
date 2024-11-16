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

namespace CmsIg\Seal;

final class EngineRegistry
{
    /**
     * @var array<string, EngineInterface>
     */
    private array $engines;

    /**
     * @param iterable<string, EngineInterface> $engines
     */
    public function __construct(
        iterable $engines,
    ) {
        $this->engines = [...$engines];
    }

    /**
     * @return iterable<string, EngineInterface>
     */
    public function getEngines(): iterable
    {
        return $this->engines;
    }

    public function getEngine(string $name): EngineInterface
    {
        if (!isset($this->engines[$name])) {
            throw new \InvalidArgumentException(
                'Unknown Search engine: "' . $name . '" available engines are "' . \implode('", "', \array_keys($this->engines)) . '".',
            );
        }

        return $this->engines[$name];
    }
}
