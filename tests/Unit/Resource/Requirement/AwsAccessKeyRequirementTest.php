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
use Ymir\Cli\Resource\Requirement\AwsAccessKeyRequirement;
use Ymir\Cli\Tests\TestCase;

class AwsAccessKeyRequirementTest extends TestCase
{
    /**
     * @var string
     */
    private $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir().'/ymir-test-'.uniqid();
        mkdir($this->tempDir.'/.aws', 0777, true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->deleteDir($this->tempDir);
    }

    public function testFulfillAsksForCredentialsWhenFileIsEmpty(): void
    {
        file_put_contents($this->tempDir.'/.aws/credentials', '');

        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);

        $context->shouldReceive('getHomeDirectory')->andReturn($this->tempDir);
        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);
        $input->shouldReceive('getStringOption')->with('aws-profile')->andReturnNull();

        $output->shouldReceive('ask')->with('What is your AWS access key ID?')->andReturn('key');
        $output->shouldReceive('askHidden')->with('What is your AWS secret access key?')->andReturn('secret');

        $requirement = new AwsAccessKeyRequirement();

        $this->assertSame(['key' => 'key', 'secret' => 'secret'], $requirement->fulfill($context));
    }

    public function testFulfillAsksForCredentialsWhenNoFileExists(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);

        $context->shouldReceive('getHomeDirectory')->andReturn($this->tempDir);
        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);
        $input->shouldReceive('getStringOption')->with('aws-profile')->andReturnNull();

        $output->shouldReceive('ask')->with('What is your AWS access key ID?')->andReturn('key');
        $output->shouldReceive('askHidden')->with('What is your AWS secret access key?')->andReturn('secret');

        $requirement = new AwsAccessKeyRequirement();

        $this->assertSame(['key' => 'key', 'secret' => 'secret'], $requirement->fulfill($context));
    }

    public function testFulfillAsksForCredentialsWhenNoProfileSelected(): void
    {
        file_put_contents($this->tempDir.'/.aws/credentials', "[default]\naws_access_key_id = key1\naws_secret_access_key = secret1");

        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);

        $context->shouldReceive('getHomeDirectory')->andReturn($this->tempDir);
        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);
        $input->shouldReceive('getStringOption')->with('aws-profile')->andReturnNull();

        $output->shouldReceive('writeln')->with('Available AWS credential profiles:');
        $output->shouldReceive('list')->with(\Mockery::on(function ($keys) {
            return $keys->contains('default');
        }));
        $output->shouldReceive('ask')->with('Which profile name would you like to use? (Press Enter to enter credentials manually)')->andReturn('');
        $output->shouldReceive('ask')->with('What is your AWS access key ID?')->andReturn('manual-key');
        $output->shouldReceive('askHidden')->with('What is your AWS secret access key?')->andReturn('manual-secret');

        $requirement = new AwsAccessKeyRequirement();

        $this->assertSame(['key' => 'manual-key', 'secret' => 'manual-secret'], $requirement->fulfill($context));
    }

    public function testFulfillRejectsUnknownGivenProfile(): void
    {
        $this->expectException(RequirementValidationException::class);
        $this->expectExceptionMessage(sprintf('The "missing" AWS credential profile isn\'t available in "%s/.aws/credentials", but you can create it or specify another profile with the "--aws-profile" option', $this->tempDir));

        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);

        $context->shouldReceive('getHomeDirectory')->andReturn($this->tempDir);
        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);
        $input->shouldReceive('getStringOption')->with('aws-profile')->andReturn('missing');

        (new AwsAccessKeyRequirement())->fulfill($context);
    }

    public function testFulfillReturnsGivenProfileCredentials(): void
    {
        file_put_contents($this->tempDir.'/.aws/credentials', "[work]\naws_access_key_id = key\naws_secret_access_key = secret");

        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);

        $context->shouldReceive('getHomeDirectory')->andReturn($this->tempDir);
        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);
        $input->shouldReceive('getStringOption')->with('aws-profile')->andReturn('work');

        $this->assertSame(['key' => 'key', 'secret' => 'secret'], (new AwsAccessKeyRequirement())->fulfill($context));
    }

    public function testFulfillReturnsSelectedProfileCredentials(): void
    {
        file_put_contents($this->tempDir.'/.aws/credentials', "[default]\naws_access_key_id = key1\naws_secret_access_key = secret1\n\n[work]\naws_access_key_id = key2\naws_secret_access_key = secret2");

        $context = \Mockery::mock(ExecutionContext::class);
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class);

        $context->shouldReceive('getHomeDirectory')->andReturn($this->tempDir);
        $context->shouldReceive('getInput')->andReturn($input);
        $context->shouldReceive('getOutput')->andReturn($output);
        $input->shouldReceive('getStringOption')->with('aws-profile')->andReturnNull();

        $output->shouldReceive('writeln')->with('Available AWS credential profiles:');
        $output->shouldReceive('list')->with(\Mockery::on(function ($keys) {
            return $keys->contains('default') && $keys->contains('work');
        }));
        $output->shouldReceive('ask')->with('Which profile name would you like to use? (Press Enter to enter credentials manually)')->andReturn('work');

        $requirement = new AwsAccessKeyRequirement();

        $this->assertSame(['key' => 'key2', 'secret' => 'secret2'], $requirement->fulfill($context));
    }

    private function deleteDir(string $dirPath): void
    {
        if (!is_dir($dirPath)) {
            return;
        }

        $files = array_diff(scandir($dirPath), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("{$dirPath}/{$file}")) ? $this->deleteDir("{$dirPath}/{$file}") : unlink("{$dirPath}/{$file}");
        }

        rmdir($dirPath);
    }
}
