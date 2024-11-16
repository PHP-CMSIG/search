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

namespace CmsIg\Seal\Integration\Spiral\Config;

use Spiral\Core\InjectableConfig;

/**
 * @experimental
 */
final class SealConfig extends InjectableConfig
{
    public const CONFIG = 'cmsig_seal';

    /**
     * @var array{
     *     index_name_prefix: string,
     *     schemas: array<string, array{
     *         dir: string,
     *         engine?: string,
     *     }>,
     *     engines: array<string, array{
     *         adapter: string,
     *     }>,
     *     reindex_providers: string[],
     * }
     */
    protected array $config = [
        'index_name_prefix' => '',
        'schemas' => [],
        'engines' => [],
        'reindex_providers' => [],
    ];

    public function getIndexNamePrefix(): string
    {
        return $this->config['index_name_prefix'];
    }

    /**
     * @return array<string, array{
     *     dir: string,
     *     engine?: string,
     * }>
     */
    public function getSchemas(): array
    {
        return $this->config['schemas'];
    }

    /**
     * @return array<string, array{
     *     adapter?: string,
     * }>
     */
    public function getEngines(): array
    {
        return $this->config['engines'];
    }

    /**
     * @return string[]
     */
    public function getReindexProviders(): array
    {
        return $this->config['reindex_providers'];
    }
}
