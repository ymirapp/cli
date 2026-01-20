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

namespace Ymir\Cli\Tests\Unit\Project\Deployment;

use Ymir\Cli\ApiClient;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Exception\ConfigurationException;
use Ymir\Cli\Exception\SystemException;
use Ymir\Cli\Executable\DockerExecutable;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\FileUploader;
use Ymir\Cli\Project\Deployment\UploadFunctionCodeStep;
use Ymir\Cli\Tests\Factory\DeploymentFactory;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Factory\ProjectFactory;
use Ymir\Cli\Tests\TestCase;

class UploadFunctionCodeStepTest extends TestCase
{
    public function testPerformPushesImageForImageDeployment(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $deployment = DeploymentFactory::create(['configuration' => ['environments' => ['staging' => ['deployment' => ['type' => 'image']]]]]);
        $environment = EnvironmentFactory::create();
        $dockerExecutable = \Mockery::mock(DockerExecutable::class);
        $output = \Mockery::mock(Output::class)->shouldIgnoreMissing();
        $project = ProjectFactory::create();
        $uploader = \Mockery::mock(FileUploader::class);

        $context->shouldReceive('getProject')->once()
                ->andReturn($project);

        $context->shouldReceive('getApiClient')->once()
                ->andReturn($apiClient);

        $context->shouldReceive('getOutput')->once()
                ->andReturn($output);

        $apiClient->shouldReceive('getDeploymentImage')->once()
                  ->with($deployment)
                  ->andReturn(collect([
                      'authorization_token' => base64_encode('user:password'),
                      'image_uri' => '123.dkr.ecr.us-east-1.amazonaws.com/project:staging',
                  ]));

        $output->shouldReceive('infoWithDelayWarning')->once()
               ->with(sprintf('Pushing <comment>%s</comment> container image', $project->getName()));

        $dockerExecutable->shouldReceive('login')->once()
                         ->with('user', 'password', '123.dkr.ecr.us-east-1.amazonaws.com', 'build');

        $dockerExecutable->shouldReceive('tag')->once()
                         ->with('project:staging', '123.dkr.ecr.us-east-1.amazonaws.com/project:staging', 'build');

        $dockerExecutable->shouldReceive('push')->once()
                         ->with('123.dkr.ecr.us-east-1.amazonaws.com/project:staging', 'build');

        $step = new UploadFunctionCodeStep('artifact.zip', 'build', $dockerExecutable, $uploader);

        $step->perform($context, $deployment, $environment);
    }

    public function testPerformThrowsExceptionWithIncompleteDeploymentImageData(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionMessage('Deployment image data is incomplete');

        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $deployment = DeploymentFactory::create(['configuration' => ['environments' => ['staging' => ['deployment' => ['type' => 'image']]]]]);
        $environment = EnvironmentFactory::create();
        $dockerExecutable = \Mockery::mock(DockerExecutable::class);
        $uploader = \Mockery::mock(FileUploader::class);

        $context->shouldReceive('getProject')->once()
                ->andReturn(ProjectFactory::create());

        $context->shouldReceive('getApiClient')->once()
                ->andReturn($apiClient);

        $apiClient->shouldReceive('getDeploymentImage')->once()
                  ->with($deployment)
                  ->andReturn(collect([]));

        $step = new UploadFunctionCodeStep('artifact.zip', 'build', $dockerExecutable, $uploader);

        $step->perform($context, $deployment, $environment);
    }

    public function testPerformThrowsExceptionWithInvalidAuthorizationTokenFormat(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionMessage('Invalid authorization token format');

        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $deployment = DeploymentFactory::create(['configuration' => ['environments' => ['staging' => ['deployment' => ['type' => 'image']]]]]);
        $environment = EnvironmentFactory::create();
        $dockerExecutable = \Mockery::mock(DockerExecutable::class);
        $uploader = \Mockery::mock(FileUploader::class);

        $context->shouldReceive('getProject')->once()
                ->andReturn(ProjectFactory::create());

        $context->shouldReceive('getApiClient')->once()
                ->andReturn($apiClient);

        $apiClient->shouldReceive('getDeploymentImage')->once()
                  ->with($deployment)
                  ->andReturn(collect([
                      'authorization_token' => base64_encode('invalid-token'),
                      'image_uri' => '123.dkr.ecr.us-east-1.amazonaws.com/project:staging',
                  ]));

        $step = new UploadFunctionCodeStep('artifact.zip', 'build', $dockerExecutable, $uploader);

        $step->perform($context, $deployment, $environment);
    }

    public function testPerformThrowsExceptionWithUnsupportedDeploymentType(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Unsupported deployment type "unsupported" for environment "staging"');

        $context = \Mockery::mock(ExecutionContext::class);
        $deployment = DeploymentFactory::create(['configuration' => ['environments' => ['staging' => ['deployment' => ['type' => 'unsupported']]]]]);
        $environment = EnvironmentFactory::create();
        $dockerExecutable = \Mockery::mock(DockerExecutable::class);
        $uploader = \Mockery::mock(FileUploader::class);

        $step = new UploadFunctionCodeStep('artifact.zip', 'build', $dockerExecutable, $uploader);

        $step->perform($context, $deployment, $environment);
    }

    public function testPerformUploadsArtifactForZipDeployment(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $deployment = DeploymentFactory::create(['configuration' => ['environments' => ['staging' => ['deployment' => ['type' => 'zip']]]]]);
        $environment = EnvironmentFactory::create();
        $dockerExecutable = \Mockery::mock(DockerExecutable::class);
        $output = \Mockery::mock(Output::class)->shouldIgnoreMissing();
        $project = ProjectFactory::create();
        $uploader = \Mockery::mock(FileUploader::class);

        $context->shouldReceive('getProject')->once()
                ->andReturn($project);

        $context->shouldReceive('getOutput')->once()
                ->andReturn($output);

        $context->shouldReceive('getApiClient')->once()
                ->andReturn($apiClient);

        $apiClient->shouldReceive('getArtifactUploadUrl')->once()
                  ->with($deployment)
                  ->andReturn('https://example.com');

        $uploader->shouldReceive('uploadFile')->once()
                 ->with('artifact.zip', 'https://example.com', \Mockery::any(), \Mockery::on(function ($progressBar) use ($project) {
                     return sprintf('Uploading <comment>%s</comment> build', $project->getName()) === $progressBar->getMessage();
                 }));

        $step = new UploadFunctionCodeStep('artifact.zip', 'build', $dockerExecutable, $uploader);

        $step->perform($context, $deployment, $environment);
    }
}
