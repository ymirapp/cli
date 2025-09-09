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

namespace Ymir\Cli\Tests\Integration\Command\Media;

use Ymir\Cli\Command\Media\ImportMediaCommand;
use Ymir\Cli\Exception\Project\UnsupportedProjectException;
use Ymir\Cli\FileUploader;
use Ymir\Cli\Project\Type\WordPressProjectType;
use Ymir\Cli\Resource\Definition\EnvironmentDefinition;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class ImportMediaCommandTest extends TestCase
{
    private $uploader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uploader = \Mockery::mock(FileUploader::class);
    }

    public function testPerformImportsMediaFiles(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'project', ['production' => []], 'wordpress', WordPressProjectType::class);
        $environment = EnvironmentFactory::create(['name' => 'production']);

        $mediaDir = $this->tempDir.'/wp-content/uploads';
        $this->filesystem->mkdir($mediaDir);
        $this->filesystem->dumpFile($mediaDir.'/image.jpg', 'image content');

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));
        $this->projectTypeMock->shouldReceive('getMediaDirectoryName')->andReturn('uploads');
        $this->projectTypeMock->shouldReceive('getMediaDirectoryPath')->withAnyArgs()->andReturn($mediaDir);
        $this->apiClient->shouldReceive('getSignedUploadRequests')->once()->andReturn(collect([
            'image.jpg' => ['uri' => 'https://s3.amazonaws.com/image.jpg', 'headers' => []],
        ]));

        $this->uploader->shouldReceive('batch')->once()->with('PUT', \Mockery::type(\Illuminate\Support\Enumerable::class))
                       ->andReturnUsing(function ($method, $requests): void {
                           foreach ($requests as $request) {
                               // Trigger iteration of lazy collection
                           }
                       });

        $this->bootApplication([new ImportMediaCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]), $this->uploader)]);

        $tester = $this->executeCommand(ImportMediaCommand::NAME, [
            'path' => $mediaDir,
            '--environment' => 'production',
            '--force' => true,
        ]);

        $this->assertStringContainsString('Starting file import to the production environment "uploads" directory', $tester->getDisplay());
        $this->assertStringContainsString('Files imported successfully to the production environment "uploads" directory', $tester->getDisplay());
    }

    public function testPerformImportsMediaFilesInteractively(): void
    {
        $this->setupActiveTeam();
        $project = $this->setupValidProject(1, 'project', ['production' => []], 'wordpress', WordPressProjectType::class);
        $environment = EnvironmentFactory::create(['name' => 'production']);

        $mediaDir = $this->tempDir.'/wp-content/uploads';
        $this->filesystem->mkdir($mediaDir);
        $this->filesystem->dumpFile($mediaDir.'/image.jpg', 'image content');

        $this->apiClient->shouldReceive('getEnvironments')->with(\Mockery::type(Project::class))->andReturn(new ResourceCollection([$environment]));
        $this->projectTypeMock->shouldReceive('getMediaDirectoryName')->andReturn('uploads');
        $this->projectTypeMock->shouldReceive('getMediaDirectoryPath')->withAnyArgs()->andReturn($mediaDir);
        $this->apiClient->shouldReceive('getSignedUploadRequests')->once()->andReturn(collect([
            'image.jpg' => ['uri' => 'https://s3.amazonaws.com/image.jpg', 'headers' => []],
        ]));

        $this->uploader->shouldReceive('batch')->once()->with('PUT', \Mockery::type(\Illuminate\Support\Enumerable::class))
                       ->andReturnUsing(function ($method, $requests): void {
                           foreach ($requests as $request) {
                               // Trigger iteration of lazy collection
                           }
                       });

        $this->bootApplication([new ImportMediaCommand($this->apiClient, $this->createExecutionContextFactory([
            Environment::class => function () { return new EnvironmentDefinition(); },
        ]), $this->uploader)]);

        $tester = $this->executeCommand(ImportMediaCommand::NAME, [], [
            'production', // Which environment
            'yes',        // Warning confirmation
        ]);

        $this->assertStringContainsString('Starting file import to the production environment "uploads" directory', $tester->getDisplay());
        $this->assertStringContainsString('Files imported successfully to the production environment "uploads" directory', $tester->getDisplay());
    }

    public function testPerformThrowsExceptionIfProjectDoesNotSupportMedia(): void
    {
        $this->expectException(UnsupportedProjectException::class);
        $this->expectExceptionMessage('This project type doesn\'t support media operations');

        $this->setupValidProject(1, 'project', [], 'laravel');

        $this->bootApplication([new ImportMediaCommand($this->apiClient, $this->createExecutionContextFactory(), $this->uploader)]);

        $this->executeCommand(ImportMediaCommand::NAME);
    }
}
