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
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\FileUploader;
use Ymir\Cli\Project\Deployment\ProcessAssetsStep;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DeploymentFactory;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Factory\ProjectFactory;
use Ymir\Cli\Tests\TestCase;

class ProcessAssetsStepTest extends TestCase
{
    public function testPerformForcesAssetsProcessing(): void
    {
        $assetsDirectory = sys_get_temp_dir().'/ymir-assets-test-'.uniqid();
        mkdir($assetsDirectory);
        file_put_contents($assetsDirectory.'/test.txt', 'test content');

        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $deployment = DeploymentFactory::create(['assets_hash' => 'hash']);
        $environment = EnvironmentFactory::create();
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class)->shouldIgnoreMissing();
        $project = ProjectFactory::create();
        $uploader = \Mockery::mock(FileUploader::class);

        $context->shouldReceive('getProject')->once()
                ->andReturn($project);

        $context->shouldReceive('getApiClient')->once()
                ->andReturn($apiClient);

        $context->shouldReceive('getOutput')->once()
                ->andReturn($output);

        $context->shouldReceive('getInput')->once()
                ->andReturn($input);

        $input->shouldReceive('getBooleanOption')->once()
              ->with('force-assets')
              ->andReturn(true);

        $apiClient->shouldReceive('getDeployments')->never();

        $output->shouldReceive('info')->once()
               ->with('Processing assets');

        $apiClient->shouldReceive('getSignedAssetRequests')->once()
                  ->andReturn(collect([
                      'test.txt' => ['command' => 'store', 'path' => 'test.txt', 'url' => 'https://example.com'],
                  ]));

        $uploader->shouldReceive('batch')->once();

        $step = new ProcessAssetsStep($assetsDirectory, $uploader);

        $step->perform($context, $deployment, $environment);

        unlink($assetsDirectory.'/test.txt');
        rmdir($assetsDirectory);
    }

    public function testPerformProcessesAssets(): void
    {
        $assetsDirectory = sys_get_temp_dir().'/ymir-assets-test-'.uniqid();
        mkdir($assetsDirectory);
        file_put_contents($assetsDirectory.'/test.txt', 'test content');

        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $deployment = DeploymentFactory::create(['assets_hash' => 'hash']);
        $environment = EnvironmentFactory::create();
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class)->shouldIgnoreMissing();
        $project = ProjectFactory::create();
        $uploader = \Mockery::mock(FileUploader::class);

        $context->shouldReceive('getProject')->once()
                ->andReturn($project);

        $context->shouldReceive('getApiClient')->once()
                ->andReturn($apiClient);

        $context->shouldReceive('getOutput')->once()
                ->andReturn($output);

        $context->shouldReceive('getInput')->once()
                ->andReturn($input);

        $input->shouldReceive('getBooleanOption')->once()
              ->with('force-assets')
              ->andReturn(false);

        $apiClient->shouldReceive('getDeployments')->once()
                  ->with($project, $environment)
                  ->andReturn(new ResourceCollection([]));

        $output->shouldReceive('info')->once()
               ->with('Processing assets');

        $apiClient->shouldReceive('getSignedAssetRequests')->once()
                  ->andReturn(collect([
                      'test.txt' => ['command' => 'store', 'path' => 'test.txt', 'url' => 'https://example.com'],
                  ]));

        $uploader->shouldReceive('batch')->once();

        $step = new ProcessAssetsStep($assetsDirectory, $uploader);

        $step->perform($context, $deployment, $environment);

        unlink($assetsDirectory.'/test.txt');
        rmdir($assetsDirectory);
    }

    public function testPerformSkipsIfNoAssetsChangeDetected(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = \Mockery::mock(ExecutionContext::class);
        $deployment = DeploymentFactory::create(['assets_hash' => 'hash']);
        $environment = EnvironmentFactory::create();
        $input = \Mockery::mock(Input::class);
        $output = \Mockery::mock(Output::class)->shouldIgnoreMissing();
        $project = ProjectFactory::create();
        $uploader = \Mockery::mock(FileUploader::class);

        $context->shouldReceive('getProject')->once()
                ->andReturn($project);

        $context->shouldReceive('getApiClient')->once()
                ->andReturn($apiClient);

        $context->shouldReceive('getOutput')->once()
                ->andReturn($output);

        $context->shouldReceive('getInput')->once()
                ->andReturn($input);

        $input->shouldReceive('getBooleanOption')->once()
              ->with('force-assets')
              ->andReturn(false);

        $apiClient->shouldReceive('getDeployments')->once()
                  ->with($project, $environment)
                  ->andReturn(new ResourceCollection([
                      ['id' => 1, 'uuid' => 'uuid', 'status' => 'finished', 'created_at' => 'now', 'configuration' => [], 'unmanaged_domains' => [], 'type' => 'zip', 'assets_hash' => 'hash'],
                  ]));

        $output->shouldReceive('infoWithWarning')->once()
               ->with('No assets change detected', 'skipping processing assets');

        $step = new ProcessAssetsStep('assets', $uploader);

        $step->perform($context, $deployment, $environment);
    }
}
