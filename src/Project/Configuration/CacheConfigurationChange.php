<?php

declare(strict_types=1);

/*
 * This file is part of Ymir command-line tool.
 *
 * (c) Carl Alexander <support@ymirapp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ymir\Cli\Project\Configuration;

class CacheConfigurationChange implements ConfigurationChangeInterface
{
    /**
     * The name of the cache to add to the configuration.
     *
     * @var string
     */
    private $cache;

    /**
     * Constructor.
     */
    public function __construct(string $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(array $options, string $projectType): array
    {
        return array_merge($options, ['cache' => $this->cache]);
    }
}
