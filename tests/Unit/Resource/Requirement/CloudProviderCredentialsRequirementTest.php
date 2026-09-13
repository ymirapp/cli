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

namespace Ymir\Cli\Tests\Unit\Resource\Requirement;

use Ymir\Cli\Exception\Resource\RequirementDependencyException;
use Ymir\Cli\Exception\Resource\RequirementValidationException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Requirement\CloudProviderCredentialsRequirement;
use Ymir\Cli\Tests\Factory\CloudProviderFactory;
use Ymir\Cli\Tests\TestCase;

class CloudProviderCredentialsRequirementTest extends TestCase
{
    public function testFulfillRequiresAuthenticationMethod(): void
    {
        $this->expectException(RequirementDependencyException::class);
        $this->expectExceptionMessage('"authentication_method" must be fulfilled before fulfilling the cloud provider credentials requirement');

        (new CloudProviderCredentialsRequirement(CloudProviderFactory::create()))->fulfill(\Mockery::mock(ExecutionContext::class));
    }

    public function testFulfillValidatesAuthenticationMethod(): void
    {
        $this->expectException(RequirementValidationException::class);
        $this->expectExceptionMessage('The cloud provider authentication method must be one of: assume_role, access_key');

        (new CloudProviderCredentialsRequirement(CloudProviderFactory::create()))->fulfill(\Mockery::mock(ExecutionContext::class), [
            'authentication_method' => 'invalid',
        ]);
    }
}
