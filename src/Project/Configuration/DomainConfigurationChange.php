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

class DomainConfigurationChange implements ConfigurationChangeInterface
{
    /**
     * The domain to add to the configuration.
     *
     * @var string
     */
    private $domain;

    /**
     * Constructor.
     */
    public function __construct(string $domain)
    {
        $this->domain = $domain;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(EnvironmentConfiguration $configuration, ProjectTypeInterface $projectType): EnvironmentConfiguration
    {
        $domains = collect($configuration->getDomains())
            ->push($this->domain)
            ->unique(function (string $domain): string {
                return strtolower($domain);
            })
            ->values()
            ->all();

        return $configuration->with(['domain' => 1 === count($domains) ? reset($domains) : $domains]);
    }
}
