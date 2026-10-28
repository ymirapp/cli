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

namespace Ymir\Cli\Project\Deployment;

use Carbon\Carbon;
use Ymir\Cli\Dockerfile;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Resource\Model\Deployment;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Support\Arr;

/**
 * Warn about upstream-EOL PHP deployments.
 */
class WarnEndOfLifePhpVersionStep implements DeploymentStepInterface
{
    /**
     * Upstream security-support end dates.
     */
    private const SECURITY_SUPPORT_END_DATES = [
        '7.2' => '2020-11-30',
        '7.3' => '2021-12-06',
        '7.4' => '2022-11-28',
        '8.0' => '2023-11-26',
        '8.1' => '2025-12-31',
        '8.2' => '2026-12-31',
        '8.3' => '2027-12-31',
        '8.4' => '2028-12-31',
        '8.5' => '2029-12-31',
    ];

    /**
     * The built project's Dockerfile.
     *
     * @var Dockerfile
     */
    private $dockerfile;

    /**
     * Constructor.
     */
    public function __construct(Dockerfile $dockerfile)
    {
        $this->dockerfile = $dockerfile;
    }

    /**
     * {@inheritdoc}
     */
    public function perform(ExecutionContext $context, Deployment $deployment, Environment $environment): void
    {
        $phpVersion = $this->getPhpVersion($deployment, $environment->getName());

        if (null === $phpVersion || !isset(self::SECURITY_SUPPORT_END_DATES[$phpVersion]) || !Carbon::now('UTC')->greaterThan(Carbon::parse(self::SECURITY_SUPPORT_END_DATES[$phpVersion], 'UTC')->endOfDay())) {
            return;
        }

        $context->getOutput()->warning(sprintf('The "<comment>%s</comment>" environment uses PHP <comment>%s</comment> which reached its upstream end of life on %s. Security backports from Ymir or other vendors don\'t change its upstream support status, so you should upgrade to a PHP version that is still supported upstream: https://www.php.net/supported-versions.php', $environment->getName(), $phpVersion, self::SECURITY_SUPPORT_END_DATES[$phpVersion]));
        $context->getOutput()->newLine();
    }

    /**
     * Get the deployment's PHP branch.
     */
    private function getPhpVersion(Deployment $deployment, string $environment): ?string
    {
        $configuration = Arr::get($deployment->getConfiguration(), 'environments.'.$environment);

        if (!is_array($configuration)) {
            return null;
        }

        $environmentConfiguration = new EnvironmentConfiguration($environment, $configuration);

        if ($environmentConfiguration->isImageDeploymentType()) {
            return 'deployment' === $deployment->getType() ? $this->dockerfile->getPhpVersion($environment) : null;
        }

        $layers = Arr::get($configuration, 'layers');
        $php = Arr::get($configuration, 'php');

        if (empty($layers)) {
            return is_string($php) ? $this->parsePhpBranch($php, '/^(\d+\.\d+)$/') : null;
        } elseif (!is_array($layers)) {
            return null;
        }

        $phpLayerVersions = collect($layers)->filter(function ($layer): bool {
            return is_string($layer);
        })->map(function (string $layer): ?string {
            return $this->parsePhpBranch($layer, '/^ymir:php-(\d+\.\d+)$/i');
        })->filter()->unique();

        return 1 === $phpLayerVersions->count() ? $phpLayerVersions->first() : null;
    }

    /**
     * Parse a PHP branch.
     */
    private function parsePhpBranch(string $value, string $pattern): ?string
    {
        return 1 === preg_match($pattern, trim($value), $matches) ? $matches[1] : null;
    }
}
