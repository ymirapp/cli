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

use Ymir\Cli\Console\Output;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Requirement\CloudProviderAuthenticationMethodRequirement;
use Ymir\Cli\Tests\TestCase;

class CloudProviderAuthenticationMethodRequirementTest extends TestCase
{
    public function testFulfillDefaultsToAssumeRole(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $output = \Mockery::mock(Output::class);

        $context->shouldReceive('getOutput')->andReturn($output);
        $output->shouldReceive('choice')->with('Which authentication method?', [
            'IAM role (recommended)',
            'Access key (legacy, less secure)',
        ], 'IAM role (recommended)')->andReturn('IAM role (recommended)');

        $requirement = new CloudProviderAuthenticationMethodRequirement('Which authentication method?');

        $this->assertSame(CloudProviderAuthenticationMethodRequirement::ASSUME_ROLE, $requirement->fulfill($context));
    }

    public function testFulfillReturnsAccessKey(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $output = \Mockery::mock(Output::class);

        $context->shouldReceive('getOutput')->andReturn($output);
        $output->shouldReceive('choice')->andReturn('Access key (legacy, less secure)');

        $requirement = new CloudProviderAuthenticationMethodRequirement('Which authentication method?');

        $this->assertSame(CloudProviderAuthenticationMethodRequirement::ACCESS_KEY, $requirement->fulfill($context));
    }
}
