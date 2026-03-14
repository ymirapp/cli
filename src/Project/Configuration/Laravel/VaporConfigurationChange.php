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

namespace Ymir\Cli\Project\Configuration\Laravel;

use Ymir\Cli\Project\Configuration\ConfigurationChangeInterface;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Support\Arr;

class VaporConfigurationChange implements ConfigurationChangeInterface
{
    /**
     * The parsed Vapor configuration.
     *
     * @var array
     */
    private $vaporConfiguration;

    /**
     * Constructor.
     */
    public function __construct(array $vaporConfiguration)
    {
        $this->vaporConfiguration = $vaporConfiguration;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(EnvironmentConfiguration $configuration, ProjectTypeInterface $projectType): EnvironmentConfiguration
    {
        $vaporEnvironmentConfiguration = Arr::get($this->vaporConfiguration, sprintf('environments.%s', $configuration->getName()));

        if (!is_array($vaporEnvironmentConfiguration)) {
            return $configuration;
        }

        $ymirEnvironmentConfiguration = $configuration->toArray();

        $ymirEnvironmentConfiguration = $this->applyDirectMappings($vaporEnvironmentConfiguration, $ymirEnvironmentConfiguration);
        $ymirEnvironmentConfiguration = $this->applyDeploymentChanges($vaporEnvironmentConfiguration, $ymirEnvironmentConfiguration);
        $ymirEnvironmentConfiguration = $this->applyCronChange($vaporEnvironmentConfiguration, $ymirEnvironmentConfiguration);
        $ymirEnvironmentConfiguration = $this->applyRuntimeChange($vaporEnvironmentConfiguration, $ymirEnvironmentConfiguration);
        $ymirEnvironmentConfiguration = $this->applyQueueChanges($vaporEnvironmentConfiguration, $ymirEnvironmentConfiguration);

        return new EnvironmentConfiguration($configuration->getName(), $ymirEnvironmentConfiguration);
    }

    /**
     * Apply the Vapor scheduler setting as a Ymir cron change.
     */
    private function applyCronChange(array $vaporEnvironmentConfiguration, array $ymirEnvironmentConfiguration): array
    {
        if (Arr::has($vaporEnvironmentConfiguration, 'scheduler') && false === $vaporEnvironmentConfiguration['scheduler']) {
            Arr::set($ymirEnvironmentConfiguration, 'cron', false);
        }

        return $ymirEnvironmentConfiguration;
    }

    /**
     * Apply deployment command changes from Vapor.
     */
    private function applyDeploymentChanges(array $vaporEnvironmentConfiguration, array $ymirEnvironmentConfiguration): array
    {
        $deployCommands = Arr::get($vaporEnvironmentConfiguration, 'deploy');

        if (!Arr::has($vaporEnvironmentConfiguration, 'deploy') || empty($deployCommands)) {
            return $ymirEnvironmentConfiguration;
        }

        $deploymentConfiguration = Arr::get($ymirEnvironmentConfiguration, 'deployment');
        $deploymentType = Arr::get($ymirEnvironmentConfiguration, 'deployment.type', $deploymentConfiguration);

        if (is_string($deploymentType)) {
            Arr::set($ymirEnvironmentConfiguration, 'deployment.type', $deploymentType);
        }

        Arr::set($ymirEnvironmentConfiguration, 'deployment.commands', $deployCommands);

        return $ymirEnvironmentConfiguration;
    }

    /**
     * Apply direct Vapor-to-Ymir key mappings.
     */
    private function applyDirectMappings(array $vaporEnvironmentConfiguration, array $ymirEnvironmentConfiguration): array
    {
        $directMappings = [
            'build.commands' => 'build',
            'console.timeout' => 'cli-timeout',
            'database.server' => 'database',
            'database.user' => 'database-user',
            'domain' => 'domain',
            'firewall.bots' => 'firewall.bot-control',
            'firewall.rate_limit' => 'firewall.rate-limit',
            'memory' => 'memory',
            'warmup' => 'warm',
            'website.concurrency' => 'concurrency',
            'website.timeout' => 'timeout',
        ];

        collect($directMappings)
            ->filter(function (string $vaporKey) use ($vaporEnvironmentConfiguration): bool {
                return Arr::has($vaporEnvironmentConfiguration, $vaporKey);
            })
            ->each(function (string $vaporKey, string $ymirKey) use ($vaporEnvironmentConfiguration, &$ymirEnvironmentConfiguration): void {
                $value = Arr::get($vaporEnvironmentConfiguration, $vaporKey);

                if (null === $value) {
                    return;
                }

                Arr::set($ymirEnvironmentConfiguration, $ymirKey, $value);
            });

        return $ymirEnvironmentConfiguration;
    }

    /**
     * Apply queue changes from Vapor queue settings.
     */
    private function applyQueueChanges(array $vaporEnvironmentConfiguration, array $ymirEnvironmentConfiguration): array
    {
        if (!Arr::hasAny($vaporEnvironmentConfiguration, ['queues', 'queue-concurrency', 'queue-memory', 'queue-timeout'])) {
            return $ymirEnvironmentConfiguration;
        } elseif (Arr::has($vaporEnvironmentConfiguration, 'queues') && false === $vaporEnvironmentConfiguration['queues']) {
            Arr::forget($ymirEnvironmentConfiguration, 'queues');

            return $ymirEnvironmentConfiguration;
        }

        $queueDefaults = $this->getVaporQueueDefaults($vaporEnvironmentConfiguration);
        $vaporQueues = $this->parseVaporQueues($vaporEnvironmentConfiguration);

        if (empty($queueDefaults) && empty($vaporQueues)) {
            return $ymirEnvironmentConfiguration;
        } elseif (empty($vaporQueues)) {
            Arr::set($ymirEnvironmentConfiguration, 'queues', $queueDefaults);

            return $ymirEnvironmentConfiguration;
        }

        $vaporQueues = collect($vaporQueues)
            ->map(function ($queueConfiguration) use ($queueDefaults): ?array {
                return !empty($queueDefaults) ? array_merge($queueDefaults, (array) $queueConfiguration) : $queueConfiguration;
            })->all();

        Arr::set($ymirEnvironmentConfiguration, 'queues', $vaporQueues);

        return $ymirEnvironmentConfiguration;
    }

    /**
     * Apply runtime-derived settings from Vapor.
     */
    private function applyRuntimeChange(array $vaporEnvironmentConfiguration, array $ymirEnvironmentConfiguration): array
    {
        if (!Arr::has($vaporEnvironmentConfiguration, 'runtime') || !is_string($vaporEnvironmentConfiguration['runtime'])) {
            return $ymirEnvironmentConfiguration;
        }

        $deploymentType = Arr::get($ymirEnvironmentConfiguration, 'deployment.type', Arr::get($ymirEnvironmentConfiguration, 'deployment'));
        $runtime = strtolower($vaporEnvironmentConfiguration['runtime']);

        if (is_string($deploymentType)) {
            $deploymentType = strtolower($deploymentType);
        }

        if (str_ends_with($runtime, '-arm')) {
            Arr::set($ymirEnvironmentConfiguration, 'architecture', 'arm64');
        }

        if ('image' !== $deploymentType && 1 === preg_match('/php-(\d+\.\d+)/', $runtime, $matches)) {
            Arr::set($ymirEnvironmentConfiguration, 'php', $matches[1]);
        }

        return $ymirEnvironmentConfiguration;
    }

    /**
     * Get numeric queue defaults from Vapor queue-* settings.
     */
    private function getVaporQueueDefaults(array $vaporEnvironmentConfiguration): array
    {
        $queueDefaults = [
            'concurrency' => 'queue-concurrency',
            'memory' => 'queue-memory',
            'timeout' => 'queue-timeout',
        ];

        return collect($queueDefaults)
            ->filter(function (string $vaporKey) use ($vaporEnvironmentConfiguration): bool {
                return Arr::has($vaporEnvironmentConfiguration, $vaporKey) && is_numeric($vaporEnvironmentConfiguration[$vaporKey]);
            })
            ->mapWithKeys(function (string $vaporKey, string $ymirKey) use ($vaporEnvironmentConfiguration): array {
                return [$ymirKey => (int) $vaporEnvironmentConfiguration[$vaporKey]];
            })
            ->all();
    }

    /**
     * Parse named queue definitions from Vapor queue settings.
     */
    private function parseVaporQueues(array $vaporEnvironmentConfiguration): array
    {
        if (!Arr::has($vaporEnvironmentConfiguration, 'queues') || !is_array($vaporEnvironmentConfiguration['queues'])) {
            return [];
        }

        return collect($vaporEnvironmentConfiguration['queues'])->mapWithKeys(function ($configuration, $name): array {
            if (is_int($name) && is_string($configuration)) {
                $name = $configuration;
                $configuration = [];
            } elseif (is_int($name) && is_array($configuration) && 1 === count($configuration) && is_string(key($configuration))) {
                $name = key($configuration);
                $configuration = reset($configuration);
            }

            if (!is_string($name)) {
                return [];
            }

            $configuration = (is_int($configuration) || is_numeric($configuration)) ? ['concurrency' => (int) $configuration] : [];

            if (str_ends_with($name, '.fifo')) {
                $name = substr($name, 0, -5);
                $configuration['type'] = 'fifo';
            }

            return [$name => $configuration];
        })->all();
    }
}
