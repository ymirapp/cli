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

namespace Ymir\Cli\ProjectConfiguration;

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
    public function apply(array $options, string $projectType): array
    {
        if (empty($options['domain'])) {
            $options['domain'] = $this->domain;
        } elseif (is_array($options['domain']) || (is_string($options['domain']) && strtolower($options['domain']) !== strtolower($this->domain))) {
            $options['domain'] = collect($this->domain)->merge($options['domain'])->unique()->values()->all();
        }

        return $options;
    }
}
