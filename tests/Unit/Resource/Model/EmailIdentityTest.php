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

use Ymir\Cli\Resource\Model\CloudProvider;
use Ymir\Cli\Resource\Model\EmailIdentity;
use Ymir\Cli\Resource\Model\Team;
use Ymir\Cli\Resource\Model\User;
use Ymir\Cli\Tests\TestCase;

class EmailIdentityTest extends TestCase
{
    public function testFromArraySetsDkimAuthenticationRecords(): void
    {
        $emailIdentity = EmailIdentity::fromArray($this->getEmailIdentityData());

        $this->assertSame(['record'], $emailIdentity->getDkimAuthenticationRecords());
    }

    public function testFromArraySetsId(): void
    {
        $emailIdentity = EmailIdentity::fromArray($this->getEmailIdentityData());

        $this->assertSame(1, $emailIdentity->getId());
    }

    public function testFromArraySetsIsManaged(): void
    {
        $emailIdentity = EmailIdentity::fromArray($this->getEmailIdentityData());

        $this->assertTrue($emailIdentity->isManaged());
    }

    public function testFromArraySetsIsVerified(): void
    {
        $emailIdentity = EmailIdentity::fromArray($this->getEmailIdentityData());

        $this->assertTrue($emailIdentity->isVerified());
    }

    public function testFromArraySetsName(): void
    {
        $emailIdentity = EmailIdentity::fromArray($this->getEmailIdentityData());

        $this->assertSame('name', $emailIdentity->getName());
    }

    public function testFromArraySetsProvider(): void
    {
        $emailIdentity = EmailIdentity::fromArray($this->getEmailIdentityData());

        $this->assertSame(2, $emailIdentity->getProvider()->getId());
    }

    public function testFromArraySetsRegion(): void
    {
        $emailIdentity = EmailIdentity::fromArray($this->getEmailIdentityData());

        $this->assertSame('region', $emailIdentity->getRegion());
    }

    public function testFromArraySetsType(): void
    {
        $emailIdentity = EmailIdentity::fromArray($this->getEmailIdentityData());

        $this->assertSame('type', $emailIdentity->getType());
    }

    public function testGetDkimAuthenticationRecords(): void
    {
        $emailIdentity = $this->createEmailIdentity();

        $this->assertSame(['record'], $emailIdentity->getDkimAuthenticationRecords());
    }

    public function testGetId(): void
    {
        $emailIdentity = $this->createEmailIdentity();

        $this->assertSame(1, $emailIdentity->getId());
    }

    public function testGetName(): void
    {
        $emailIdentity = $this->createEmailIdentity();

        $this->assertSame('name', $emailIdentity->getName());
    }

    public function testGetProvider(): void
    {
        $emailIdentity = $this->createEmailIdentity();

        $this->assertInstanceOf(CloudProvider::class, $emailIdentity->getProvider());
    }

    public function testGetRegion(): void
    {
        $emailIdentity = $this->createEmailIdentity();

        $this->assertSame('region', $emailIdentity->getRegion());
    }

    public function testGetType(): void
    {
        $emailIdentity = $this->createEmailIdentity();

        $this->assertSame('type', $emailIdentity->getType());
    }

    public function testIsManaged(): void
    {
        $emailIdentity = $this->createEmailIdentity();

        $this->assertTrue($emailIdentity->isManaged());
    }

    public function testIsVerified(): void
    {
        $emailIdentity = $this->createEmailIdentity();

        $this->assertTrue($emailIdentity->isVerified());
    }

    private function createEmailIdentity(): EmailIdentity
    {
        $user = new User(3, 'owner');
        $team = new Team(2, 'team', $user);
        $provider = new CloudProvider(1, 'provider', $team);

        return new EmailIdentity(1, 'name', 'region', $provider, 'type', true, true, ['record']);
    }

    private function getEmailIdentityData(): array
    {
        return [
            'id' => 1,
            'name' => 'name',
            'region' => 'region',
            'type' => 'type',
            'verified' => true,
            'managed' => true,
            'dkim_authentication_records' => ['record'],
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
