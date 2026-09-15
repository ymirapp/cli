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

use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Exception\Resource\RequirementValidationException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Requirement\CloudProviderAuthenticationMethodRequirement;
use Ymir\Cli\Tests\TestCase;

class CloudProviderAuthenticationMethodRequirementTest extends TestCase
{
    public function testFulfillDefaultsNullToAssumeRole(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);

        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);
        $input->shouldReceive('getStringOption')->with('aws-profile')->andReturnNull();
        $input->shouldReceive('hasOption')->with('role-arn')->andReturn(false);
        $input->shouldReceive('getStringOption')->with('role-arn')->andReturnNull();
        $input->shouldReceive('isInteractive')->andReturn(true);
        $output->shouldReceive('choice')->with('Which authentication method?', [
            'IAM role (recommended)',
            'Access key (legacy, less secure)',
        ], 'IAM role (recommended)')->andReturn('IAM role (recommended)');

        $requirement = new CloudProviderAuthenticationMethodRequirement('Which authentication method?', null);

        $this->assertSame(CloudProviderAuthenticationMethodRequirement::ASSUME_ROLE, $requirement->fulfill($context));
    }

    public function testFulfillInfersAccessKeyFromAwsProfileOption(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);

        $context->shouldReceive('getInput')->andReturn($input);
        $input->shouldReceive('getStringOption')->with('aws-profile')->andReturn('work');
        $input->shouldReceive('hasOption')->with('role-arn')->andReturn(true);
        $input->shouldReceive('getStringOption')->with('role-arn')->andReturnNull();

        $this->assertSame(CloudProviderAuthenticationMethodRequirement::ACCESS_KEY, (new CloudProviderAuthenticationMethodRequirement('Which authentication method?'))->fulfill($context));
    }

    public function testFulfillInfersAssumeRoleFromRoleArnOption(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);

        $context->shouldReceive('getInput')->andReturn($input);
        $input->shouldReceive('getStringOption')->with('aws-profile')->andReturnNull();
        $input->shouldReceive('hasOption')->with('role-arn')->andReturn(true);
        $input->shouldReceive('getStringOption')->with('role-arn')->andReturn('arn:aws:iam::444455556666:role/customer-role');

        $this->assertSame(CloudProviderAuthenticationMethodRequirement::ASSUME_ROLE, (new CloudProviderAuthenticationMethodRequirement('Which authentication method?'))->fulfill($context));
    }

    public function testFulfillRejectsBothAuthenticationOptions(): void
    {
        $this->expectException(RequirementValidationException::class);
        $this->expectExceptionMessage('The "--aws-profile" and "--role-arn" options cannot be used together');

        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);

        $context->shouldReceive('getInput')->andReturn($input);
        $input->shouldReceive('getStringOption')->with('aws-profile')->andReturn('work');
        $input->shouldReceive('hasOption')->with('role-arn')->andReturn(true);
        $input->shouldReceive('getStringOption')->with('role-arn')->andReturn('arn:aws:iam::444455556666:role/customer-role');

        (new CloudProviderAuthenticationMethodRequirement('Which authentication method?'))->fulfill($context);
    }

    public function testFulfillRequiresAuthenticationOptionNonInteractively(): void
    {
        $this->expectException(RequirementValidationException::class);
        $this->expectExceptionMessage('You must enter the "--aws-profile" option when configuring cloud provider authentication non-interactively');

        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);

        $context->shouldReceive('getInput')->andReturn($input);
        $input->shouldReceive('getStringOption')->with('aws-profile')->andReturnNull();
        $input->shouldReceive('hasOption')->with('role-arn')->andReturn(false);
        $input->shouldReceive('getStringOption')->with('role-arn')->andReturnNull();
        $input->shouldReceive('isInteractive')->andReturn(false);

        (new CloudProviderAuthenticationMethodRequirement('Which authentication method?'))->fulfill($context);
    }

    public function testFulfillReturnsAccessKey(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);

        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);
        $input->shouldReceive('getStringOption')->with('aws-profile')->andReturnNull();
        $input->shouldReceive('hasOption')->with('role-arn')->andReturn(false);
        $input->shouldReceive('getStringOption')->with('role-arn')->andReturnNull();
        $input->shouldReceive('isInteractive')->andReturn(true);
        $output->shouldReceive('choice')->andReturn('Access key (legacy, less secure)');

        $requirement = new CloudProviderAuthenticationMethodRequirement('Which authentication method?');

        $this->assertSame(CloudProviderAuthenticationMethodRequirement::ACCESS_KEY, $requirement->fulfill($context));
    }

    public function testFulfillUsesGivenDefault(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);

        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);
        $input->shouldReceive('getStringOption')->with('aws-profile')->andReturnNull();
        $input->shouldReceive('hasOption')->with('role-arn')->andReturn(false);
        $input->shouldReceive('getStringOption')->with('role-arn')->andReturnNull();
        $input->shouldReceive('isInteractive')->andReturn(true);
        $output->shouldReceive('choice')->with('Which authentication method?', [
            'IAM role (recommended)',
            'Access key (legacy, less secure)',
        ], 'Access key (legacy, less secure)')->andReturn('Access key (legacy, less secure)');

        $this->assertSame(CloudProviderAuthenticationMethodRequirement::ACCESS_KEY, (new CloudProviderAuthenticationMethodRequirement('Which authentication method?', CloudProviderAuthenticationMethodRequirement::ACCESS_KEY))->fulfill($context));
    }

    public function testRejectsInvalidDefault(): void
    {
        $this->expectException(RequirementValidationException::class);
        $this->expectExceptionMessage('The "invalid" cloud provider authentication method must be one of: assume_role, access_key');

        new CloudProviderAuthenticationMethodRequirement('Which authentication method?', 'invalid');
    }
}
