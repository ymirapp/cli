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

namespace Ymir\Cli\Project\Initialization;

use Illuminate\Support\Collection;
use Ymir\Cli\Executable\WpCliExecutable;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Project\Configuration\AggregateConfigurationChange;
use Ymir\Cli\Project\Configuration\ConfigurationChangeInterface;
use Ymir\Cli\Project\Configuration\WordPress\WordPressConfigurationChangeInterface;
use Ymir\Cli\Support\Arr;

class WordPressConfigurationInitializationStep implements InitializationStepInterface
{
    /**
     * Collection of all the WordPress configuration changes.
     *
     * @var Collection
     */
    private $configurationChanges;

    /**
     * The WP-CLI executable.
     *
     * @var WpCliExecutable
     */
    private $wpCliExecutable;

    /**
     * Constructor.
     */
    public function __construct(WpCliExecutable $wpCliExecutable, iterable $configurationChanges = [])
    {
        $this->configurationChanges = new Collection();
        $this->wpCliExecutable = $wpCliExecutable;

        foreach ($configurationChanges as $configurationChange) {
            $this->addConfigurationChange($configurationChange);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function perform(ExecutionContext $context, array $projectRequirements): ?ConfigurationChangeInterface
    {
        if (!$this->wpCliExecutable->isInstalled() || !$this->wpCliExecutable->isWordPressInstalled() || !$context->getOutput()->confirm('Do you want to have Ymir scan your plugins and themes and configure your project?')) {
            return null;
        }

        $output = $context->getOutput();
        $plugins = $this->wpCliExecutable->listPlugins()->groupBy('status')->only(['active', 'must-use'])->flatten(1);
        $pluginConfigurationChanges = $this->configurationChanges->filter(function (WordPressConfigurationChangeInterface $configurationChange) use ($plugins): bool {
            return $plugins->contains('name', $configurationChange->getName());
        });

        if ($pluginConfigurationChanges->isEmpty()) {
            $output->info('No plugins or themes requiring configuration were detected');

            return null;
        }

        $output->info('Applying configuration changes for the following <comment>active</comment> plugins:');
        $output->list($pluginConfigurationChanges->map(function (WordPressConfigurationChangeInterface $configurationChange) use ($plugins) {
            $name = $configurationChange->getName();

            return Arr::get($plugins->firstWhere('name', $name), 'title', $name);
        }));

        return new AggregateConfigurationChange($pluginConfigurationChanges);
    }

    /**
     * Add a configuration change to the initialization step.
     */
    private function addConfigurationChange(WordPressConfigurationChangeInterface $configurationChange): void
    {
        $this->configurationChanges->push($configurationChange);
    }
}
