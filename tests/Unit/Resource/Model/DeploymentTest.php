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

use Ymir\Cli\Resource\Model\Deployment;
use Ymir\Cli\Resource\Model\User;
use Ymir\Cli\Tests\TestCase;

class DeploymentTest extends TestCase
{
    public function testFromArraySetsAssetsHash(): void
    {
        $deployment = Deployment::fromArray($this->getDeploymentData());

        $this->assertSame('hash', $deployment->getAssetsHash());
    }

    public function testFromArraySetsConfiguration(): void
    {
        $deployment = Deployment::fromArray($this->getDeploymentData());

        $this->assertSame(['config'], $deployment->getConfiguration());
    }

    public function testFromArraySetsCreatedAt(): void
    {
        $deployment = Deployment::fromArray($this->getDeploymentData());

        $this->assertSame('created_at', $deployment->getCreatedAt());
    }

    public function testFromArraySetsFailedMessage(): void
    {
        $deployment = Deployment::fromArray($this->getDeploymentData());

        $this->assertSame('failed', $deployment->getFailedMessage());
    }

    public function testFromArraySetsId(): void
    {
        $deployment = Deployment::fromArray($this->getDeploymentData());

        $this->assertSame(1, $deployment->getId());
    }

    public function testFromArraySetsInitiator(): void
    {
        $deployment = Deployment::fromArray($this->getDeploymentData());

        $this->assertSame(2, $deployment->getInitiator()->getId());
    }

    public function testFromArraySetsStatus(): void
    {
        $deployment = Deployment::fromArray($this->getDeploymentData());

        $this->assertSame('status', $deployment->getStatus());
    }

    public function testFromArraySetsSteps(): void
    {
        $deployment = Deployment::fromArray($this->getDeploymentData());

        $this->assertSame(['steps'], $deployment->getSteps());
    }

    public function testFromArraySetsType(): void
    {
        $deployment = Deployment::fromArray($this->getDeploymentData());

        $this->assertSame('type', $deployment->getType());
    }

    public function testFromArraySetsUnmanagedDomains(): void
    {
        $deployment = Deployment::fromArray($this->getDeploymentData());

        $this->assertSame(['domain'], $deployment->getUnmanagedDomains());
    }

    public function testFromArraySetsUuid(): void
    {
        $deployment = Deployment::fromArray($this->getDeploymentData());

        $this->assertSame('uuid', $deployment->getUuid());
    }

    public function testGetAssetsHash(): void
    {
        $deployment = $this->createDeploymentModel();

        $this->assertSame('hash', $deployment->getAssetsHash());
    }

    public function testGetConfiguration(): void
    {
        $deployment = $this->createDeploymentModel();

        $this->assertSame(['config'], $deployment->getConfiguration());
    }

    public function testGetCreatedAt(): void
    {
        $deployment = $this->createDeploymentModel();

        $this->assertSame('created_at', $deployment->getCreatedAt());
    }

    public function testGetFailedMessage(): void
    {
        $deployment = $this->createDeploymentModel();

        $this->assertSame('failed', $deployment->getFailedMessage());
    }

    public function testGetId(): void
    {
        $deployment = $this->createDeploymentModel();

        $this->assertSame(1, $deployment->getId());
    }

    public function testGetInitiator(): void
    {
        $deployment = $this->createDeploymentModel();

        $this->assertInstanceOf(User::class, $deployment->getInitiator());
    }

    public function testGetName(): void
    {
        $deployment = $this->createDeploymentModel();

        $this->assertSame('uuid', $deployment->getName());
    }

    public function testGetStatus(): void
    {
        $deployment = $this->createDeploymentModel();

        $this->assertSame('status', $deployment->getStatus());
    }

    public function testGetSteps(): void
    {
        $deployment = $this->createDeploymentModel();

        $this->assertSame(['steps'], $deployment->getSteps());
    }

    public function testGetType(): void
    {
        $deployment = $this->createDeploymentModel();

        $this->assertSame('type', $deployment->getType());
    }

    public function testGetUnmanagedDomains(): void
    {
        $deployment = $this->createDeploymentModel();

        $this->assertSame(['domain'], $deployment->getUnmanagedDomains());
    }

    public function testGetUuid(): void
    {
        $deployment = $this->createDeploymentModel();

        $this->assertSame('uuid', $deployment->getUuid());
    }

    private function createDeploymentModel(): Deployment
    {
        return new Deployment(1, 'uuid', 'status', 'created_at', ['config'], 'type', ['domain'], ['steps'], new User(2, 'initiator'), 'failed', 'hash');
    }

    private function getDeploymentData(): array
    {
        return [
            'id' => 1,
            'uuid' => 'uuid',
            'status' => 'status',
            'created_at' => 'created_at',
            'configuration' => ['config'],
            'type' => 'type',
            'unmanaged_domains' => ['domain'],
            'steps' => ['steps'],
            'initiator' => [
                'id' => 2,
                'name' => 'initiator',
            ],
            'failed_message' => 'failed',
            'assets_hash' => 'hash',
        ];
    }
}
