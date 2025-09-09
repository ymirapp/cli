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

namespace Ymir\Cli\Tests\Unit\Resource\Model;

use Ymir\Cli\Resource\Model\Certificate;
use Ymir\Cli\Resource\Model\CloudProvider;
use Ymir\Cli\Resource\Model\Team;
use Ymir\Cli\Resource\Model\User;
use Ymir\Cli\Tests\TestCase;

class CertificateTest extends TestCase
{
    public function testFromArraySetsDomains(): void
    {
        $certificate = Certificate::fromArray($this->getCertificateData());

        $this->assertSame(['domain.com'], $certificate->getDomains());
    }

    public function testFromArraySetsId(): void
    {
        $certificate = Certificate::fromArray($this->getCertificateData());

        $this->assertSame(1, $certificate->getId());
    }

    public function testFromArraySetsInUse(): void
    {
        $certificate = Certificate::fromArray($this->getCertificateData());

        $this->assertTrue($certificate->isInUse());
    }

    public function testFromArraySetsName(): void
    {
        $certificate = Certificate::fromArray($this->getCertificateData());

        $this->assertSame('1', $certificate->getName());
    }

    public function testFromArraySetsRegion(): void
    {
        $certificate = Certificate::fromArray($this->getCertificateData());

        $this->assertSame('region', $certificate->getRegion());
    }

    public function testFromArraySetsStatus(): void
    {
        $certificate = Certificate::fromArray($this->getCertificateData());

        $this->assertSame('status', $certificate->getStatus());
    }

    public function testGetDomains(): void
    {
        $user = new User(3, 'owner');
        $team = new Team(2, 'team', $user);
        $provider = new CloudProvider(1, 'provider', $team);
        $certificate = new Certificate(1, 'name', 'region', $provider, 'status', true, ['domain.com']);

        $this->assertSame(['domain.com'], $certificate->getDomains());
    }

    public function testGetId(): void
    {
        $user = new User(3, 'owner');
        $team = new Team(2, 'team', $user);
        $provider = new CloudProvider(1, 'provider', $team);
        $certificate = new Certificate(1, 'name', 'region', $provider, 'status', true, ['domain.com']);

        $this->assertSame(1, $certificate->getId());
    }

    public function testGetName(): void
    {
        $user = new User(3, 'owner');
        $team = new Team(2, 'team', $user);
        $provider = new CloudProvider(1, 'provider', $team);
        $certificate = new Certificate(1, 'name', 'region', $provider, 'status', true, ['domain.com']);

        $this->assertSame('name', $certificate->getName());
    }

    public function testGetProvider(): void
    {
        $user = new User(3, 'owner');
        $team = new Team(2, 'team', $user);
        $provider = new CloudProvider(1, 'provider', $team);
        $certificate = new Certificate(1, 'name', 'region', $provider, 'status', true, ['domain.com']);

        $this->assertSame($provider, $certificate->getProvider());
    }

    public function testGetRegion(): void
    {
        $user = new User(3, 'owner');
        $team = new Team(2, 'team', $user);
        $provider = new CloudProvider(1, 'provider', $team);
        $certificate = new Certificate(1, 'name', 'region', $provider, 'status', true, ['domain.com']);

        $this->assertSame('region', $certificate->getRegion());
    }

    public function testGetStatus(): void
    {
        $user = new User(3, 'owner');
        $team = new Team(2, 'team', $user);
        $provider = new CloudProvider(1, 'provider', $team);
        $certificate = new Certificate(1, 'name', 'region', $provider, 'status', true, ['domain.com']);

        $this->assertSame('status', $certificate->getStatus());
    }

    public function testGetValidationRecords(): void
    {
        $user = new User(3, 'owner');
        $team = new Team(2, 'team', $user);
        $provider = new CloudProvider(1, 'provider', $team);
        $domains = [
            [
                'managed' => false,
                'validation_record' => [
                    'name' => 'name1',
                    'value' => 'value1',
                ],
            ],
            [
                'managed' => true,
                'validation_record' => [
                    'name' => 'name2',
                    'value' => 'value2',
                ],
            ],
            [
                'managed' => false,
                'validation_record' => [
                    'name' => 'name1',
                    'value' => 'value1',
                ],
            ],
        ];
        $certificate = new Certificate(1, 'name', 'region', $provider, 'status', true, $domains);

        $this->assertSame([['CNAME', 'name1', 'value1']], array_values($certificate->getValidationRecords()));
    }

    public function testIsInUse(): void
    {
        $user = new User(3, 'owner');
        $team = new Team(2, 'team', $user);
        $provider = new CloudProvider(1, 'provider', $team);
        $certificate = new Certificate(1, 'name', 'region', $provider, 'status', true, ['domain.com']);

        $this->assertTrue($certificate->isInUse());
    }

    private function getCertificateData(): array
    {
        return [
            'id' => 1,
            'region' => 'region',
            'status' => 'status',
            'in_use' => true,
            'domains' => ['domain.com'],
            'provider' => [
                'id' => 2,
                'name' => 'provider',
                'team' => [
                    'id' => 3,
                    'name' => 'team',
                    'owner' => [
                        'id' => 4,
                        'name' => 'owner',
                    ],
                ],
            ],
        ];
    }
}
