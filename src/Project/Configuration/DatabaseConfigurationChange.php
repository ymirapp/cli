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

use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Support\Arr;

class DatabaseConfigurationChange implements ConfigurationChangeInterface
{
    /**
     * The prefix to use when adding the name of the database to the configuration.
     *
     * @var string|null
     */
    private $prefix;

    /**
     * The name of the database server to add to the configuration.
     *
     * @var string
     */
    private $server;

    /**
     * Constructor.
     */
    public function __construct(string $server, ?string $prefix = null)
    {
        $this->prefix = $prefix;
        $this->server = $server;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(EnvironmentConfiguration $configuration, ProjectTypeInterface $projectType): EnvironmentConfiguration
    {
        $configurationChange = [
            'database' => [
                'server' => $this->server,
            ],
        ];

        if (null !== $this->prefix) {
            Arr::set($configurationChange, 'database.name', $this->prefix.$configuration->getName());
        }

        return $configuration->with($configurationChange);
    }
}
