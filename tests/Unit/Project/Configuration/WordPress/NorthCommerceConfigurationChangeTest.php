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

namespace Ymir\Cli\Tests\Unit\Project\Configuration\WordPress;

use Ymir\Cli\Project\Configuration\WordPress\NorthCommerceConfigurationChange;
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\Type\AbstractWordPressProjectType;
use Ymir\Cli\Tests\TestCase;

class NorthCommerceConfigurationChangeTest extends TestCase
{
    private $configurationChange;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->configurationChange = new NorthCommerceConfigurationChange();
    }

    public function testApplyWithNoImageDeployment(): void
    {
        $projectType = \Mockery::mock(AbstractWordPressProjectType::class);

        $this->assertSame([
            'cdn' => [
                'cookies_whitelist' => ['nc-cart-order-id'],
                'excluded_paths' => ['/cart', '/checkout'],
            ],
        ], $this->configurationChange->apply(new EnvironmentConfiguration('staging', []), $projectType)->toArray());
    }

    public function testGetName(): void
    {
        $this->assertSame('north-commerce', $this->configurationChange->getName());
    }
}
