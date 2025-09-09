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
use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Tests\TestCase;

class DomainConfigurationChangeTest extends TestCase
{
    public function testApplyAppendsNewDomainToExistingDomainOption(): void
    {
        $existingDomain = $this->faker->domainName;
        $newDomain = $this->faker->domainName;

        $this->assertSame([
            'domain' => [$existingDomain, $newDomain],
        ], (new DomainConfigurationChange($newDomain))->apply(new EnvironmentConfiguration('staging', ['domain' => $existingDomain]), \Mockery::mock(ProjectTypeInterface::class))->toArray());
    }

    public function testApplyDoesNothingIfNewDomainIsAlreadyInDomainOption(): void
    {
        $existingDomain = $this->faker->domainName;
        $newDomain = $this->faker->domainName;

        $this->assertSame([
            'domain' => [$existingDomain, $newDomain],
        ], (new DomainConfigurationChange($newDomain))->apply(new EnvironmentConfiguration('staging', ['domain' => [$existingDomain, $newDomain]]), \Mockery::mock(ProjectTypeInterface::class))->toArray());
    }

    public function testApplyDoesNothingIfNewDomainSameAsExistingStringDomainOption(): void
    {
        $domain = $this->faker->domainName;

        $this->assertSame([
            'domain' => $domain,
        ], (new DomainConfigurationChange($domain))->apply(new EnvironmentConfiguration('staging', ['domain' => $domain]), \Mockery::mock(ProjectTypeInterface::class))->toArray());
    }

    public function testApplyIsCaseInsensitive(): void
    {
        $this->assertSame([
            'domain' => 'EXAMPLE.COM',
        ], (new DomainConfigurationChange('example.com'))->apply(new EnvironmentConfiguration('staging', ['domain' => 'EXAMPLE.COM']), \Mockery::mock(ProjectTypeInterface::class))->toArray());
    }

    public function testApplyWithNoDomainOption(): void
    {
        $domain = $this->faker->domainName;

        $this->assertSame([
            'domain' => $domain,
        ], (new DomainConfigurationChange($domain))->apply(new EnvironmentConfiguration('staging', []), \Mockery::mock(ProjectTypeInterface::class))->toArray());
    }
}
