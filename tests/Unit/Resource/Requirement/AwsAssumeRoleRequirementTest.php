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
use Ymir\Cli\Exception\Resource\RequirementFulfillmentException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Requirement\AwsAssumeRoleRequirement;
use Ymir\Cli\Tests\Factory\CloudProviderFactory;
use Ymir\Cli\Tests\TestCase;

class AwsAssumeRoleRequirementTest extends TestCase
{
    public function testFulfillRequiresAssumeRoleSetupValues(): void
    {
        $this->expectException(RequirementFulfillmentException::class);
        $this->expectExceptionMessage('Unable to fulfill the requirement: the Ymir API did not return the setup values needed to create the IAM role');

        (new AwsAssumeRoleRequirement(CloudProviderFactory::create(['authentication' => []])))->fulfill(\Mockery::mock(ExecutionContext::class));
    }

    public function testFulfillReturnsRoleArnAfterDisplayingServerSetupValues(): void
    {
        $provider = CloudProviderFactory::create([
            'authentication' => [
                'method' => null,
                'assume_role' => [
                    'ymir_account_id' => '111122223333',
                    'external_id' => 'server-external-id',
                    'role_name' => 'server-role-name',
                ],
            ],
        ]);
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);

        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);
        $input->shouldReceive('hasArgument')->once()->with('role_arn')->andReturn(false);
        $output->shouldReceive('writeln')->once()->with('Create the AWS IAM role using these values:');
        $output->shouldReceive('writeln')->once()->with('Ymir AWS account ID: 111122223333');
        $output->shouldReceive('writeln')->once()->with('External ID: server-external-id');
        $output->shouldReceive('writeln')->once()->with('Role name: server-role-name');
        $output->shouldReceive('ask')->once()->with('What is the AWS IAM role ARN?', null, \Mockery::type('callable'))->andReturn('arn:aws:iam::444455556666:role/customer-role');

        $this->assertSame(['role_arn' => 'arn:aws:iam::444455556666:role/customer-role'], (new AwsAssumeRoleRequirement($provider))->fulfill($context));
    }
}
