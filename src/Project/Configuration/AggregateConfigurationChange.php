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

class AggregateConfigurationChange implements ConfigurationChangeInterface
{
    /**
     * The configuration changes to apply.
     *
     * @var ConfigurationChangeInterface[]
     */
    private $configurationChanges;

    /**
     * Constructor.
     */
    public function __construct(iterable $configurationChanges)
    {
        $this->configurationChanges = [];

        foreach ($configurationChanges as $configurationChange) {
            $this->addConfigurationChange($configurationChange);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function apply(EnvironmentConfiguration $configuration, ProjectTypeInterface $projectType): EnvironmentConfiguration
    {
        foreach ($this->configurationChanges as $change) {
            $configuration = $change->apply($configuration, $projectType);
        }

        return $configuration;
    }

    /**
     * Add a configuration change.
     */
    private function addConfigurationChange(ConfigurationChangeInterface $configurationChange): void
    {
        $this->configurationChanges[] = $configurationChange;
    }
}
