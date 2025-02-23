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

namespace Ymir\Cli\Tests\Unit\Project\Configuration;

use Ymir\Cli\Project\Configuration\DomainConfigurationChange;
use Ymir\Cli\Tests\Mock\ProjectTypeInterfaceMockTrait;
use Ymir\Cli\Tests\Unit\TestCase;

/**
 * @covers \Ymir\Cli\Project\Configuration\DomainConfigurationChange
 */
class DomainConfigurationChangeTest extends TestCase
{
    use ProjectTypeInterfaceMockTrait;

    public function testApplyDoesNothingIfNewDomainIsAlreadyOnTopInDomainOption()
    {
        $existingDomain = $this->faker->domainName;
        $newDomain = $this->faker->domainName;

        $this->assertSame([
            'domain' => [$newDomain, $existingDomain],
        ], (new DomainConfigurationChange($newDomain))->apply(['domain' => [$newDomain, $existingDomain]], $this->getProjectTypeInterfaceMock()));
    }

    public function testApplyDoesNothingIfNewDomainSameAsExistingStringDomainOption()
    {
        $domain = $this->faker->domainName;

        $this->assertSame([
            'domain' => $domain,
        ], (new DomainConfigurationChange($domain))->apply(['domain' => $domain], $this->getProjectTypeInterfaceMock()));
    }

    public function testApplyWithMovesNewDomainToTopIfExistsInDomainOption()
    {
        $existingDomain = $this->faker->domainName;
        $newDomain = $this->faker->domainName;

        $this->assertSame([
            'domain' => [$newDomain, $existingDomain],
        ], (new DomainConfigurationChange($newDomain))->apply(['domain' => [$existingDomain, $newDomain]], $this->getProjectTypeInterfaceMock()));
    }

    public function testApplyWithNoDomainOption()
    {
        $domain = $this->faker->domainName;

        $this->assertSame([
            'domain' => $domain,
        ], (new DomainConfigurationChange($domain))->apply([], $this->getProjectTypeInterfaceMock()));
    }

    public function testApplyWithPrependsNewDomainToExistingDomainOption()
    {
        $existingDomain = $this->faker->domainName;
        $newDomain = $this->faker->domainName;

        $this->assertSame([
            'domain' => [$newDomain, $existingDomain],
        ], (new DomainConfigurationChange($newDomain))->apply(['domain' => $existingDomain], $this->getProjectTypeInterfaceMock()));
    }
}
