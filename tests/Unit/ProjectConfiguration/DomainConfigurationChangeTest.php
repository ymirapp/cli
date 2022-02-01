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

namespace Ymir\Cli\Tests\Unit\ProjectConfiguration;

use Ymir\Cli\ProjectConfiguration\DomainConfigurationChange;
use Ymir\Cli\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Cli\ProjectConfiguration\DomainConfigurationChange
 */
class DomainConfigurationChangeTest extends TestCase
{
    public function testApplyDoesNothingIfNewDomainIsAlreadyOnTopInDomainOption()
    {
        $existingDomain = $this->faker->domainName;
        $newDomain = $this->faker->domainName;

        $this->assertSame([
            'domain' => [$newDomain, $existingDomain],
        ], (new DomainConfigurationChange($newDomain))->apply(['domain' => [$newDomain, $existingDomain]], 'wordpress'));
    }

    public function testApplyDoesNothingIfNewDomainSameAsExistingStringDomainOption()
    {
        $domain = $this->faker->domainName;

        $this->assertSame([
            'domain' => $domain,
        ], (new DomainConfigurationChange($domain))->apply(['domain' => $domain], 'wordpress'));
    }

    public function testApplyWithMovesNewDomainToTopIfExistsInDomainOption()
    {
        $existingDomain = $this->faker->domainName;
        $newDomain = $this->faker->domainName;

        $this->assertSame([
            'domain' => [$newDomain, $existingDomain],
        ], (new DomainConfigurationChange($newDomain))->apply(['domain' => [$existingDomain, $newDomain]], 'wordpress'));
    }

    public function testApplyWithNoDomainOption()
    {
        $domain = $this->faker->domainName;

        $this->assertSame([
            'domain' => $domain,
        ], (new DomainConfigurationChange($domain))->apply([], 'wordpress'));
    }

    public function testApplyWithPrependsNewDomainToExistingDomainOption()
    {
        $existingDomain = $this->faker->domainName;
        $newDomain = $this->faker->domainName;

        $this->assertSame([
            'domain' => [$newDomain, $existingDomain],
        ], (new DomainConfigurationChange($newDomain))->apply(['domain' => $existingDomain], 'wordpress'));
    }
}
