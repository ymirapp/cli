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

namespace Ymir\Cli\Tests\Unit;

use Symfony\Component\DependencyInjection\ServiceLocator;
use Ymir\Cli\ApiClient;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Exception\RuntimeException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Project\ProjectConfiguration;
use Ymir\Cli\Resource\Definition\ProvisionableResourceDefinitionInterface;
use Ymir\Cli\Resource\Definition\ResolvableResourceDefinitionInterface;
use Ymir\Cli\Resource\Model\Team;
use Ymir\Cli\Resource\Requirement\RequirementInterface;
use Ymir\Cli\Resource\ResourceProvisioner;
use Ymir\Cli\Tests\Factory\ProjectFactory;
use Ymir\Cli\Tests\Factory\SecretFactory;
use Ymir\Cli\Tests\TestCase;

class ExecutionContextTest extends TestCase
{
    public function testFulfill(): void
    {
        $context = $this->createExecutionContext();
        $requirement = \Mockery::mock(RequirementInterface::class);
        $requirement->shouldReceive('fulfill')
                    ->once()
                    ->with($context, ['foo' => 'bar'])
                    ->andReturn('baz');

        $this->assertSame('baz', $context->fulfill($requirement, ['foo' => 'bar']));
    }

    public function testGetApiClient(): void
    {
        $apiClient = \Mockery::mock(ApiClient::class);
        $context = $this->createExecutionContext(['apiClient' => $apiClient]);

        $this->assertSame($apiClient, $context->getApiClient());
    }

    public function testGetHomeDirectory(): void
    {
        $context = $this->createExecutionContext(['homeDirectory' => '/foo/bar']);

        $this->assertSame('/foo/bar', $context->getHomeDirectory());
    }

    public function testGetInput(): void
    {
        $input = \Mockery::mock(Input::class);
        $context = $this->createExecutionContext(['input' => $input]);

        $this->assertSame($input, $context->getInput());
    }

    public function testGetOutput(): void
    {
        $output = \Mockery::mock(Output::class);
        $context = $this->createExecutionContext(['output' => $output]);

        $this->assertSame($output, $context->getOutput());
    }

    public function testGetProject(): void
    {
        $project = ProjectFactory::create();
        $context = $this->createExecutionContext(['project' => $project]);

        $this->assertSame($project, $context->getProject());
    }

    public function testGetProjectConfiguration(): void
    {
        $projectConfiguration = \Mockery::mock(ProjectConfiguration::class);
        $context = $this->createExecutionContext(['projectConfiguration' => $projectConfiguration]);

        $this->assertSame($projectConfiguration, $context->getProjectConfiguration());
    }

    public function testGetProjectDirectory(): void
    {
        $context = $this->createExecutionContext(['projectDirectory' => '/foo/bar']);

        $this->assertSame('/foo/bar', $context->getProjectDirectory());
    }

    public function testGetProjectOrFail(): void
    {
        $project = ProjectFactory::create();
        $context = $this->createExecutionContext(['project' => $project]);

        $this->assertSame($project, $context->getProjectOrFail());
    }

    public function testGetProjectOrFailThrowsExceptionWhenProjectIsMissing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No Ymir project found in the current directory');

        $this->createExecutionContext(['project' => null])->getProjectOrFail();
    }

    public function testGetProvisioner(): void
    {
        $provisioner = \Mockery::mock(ResourceProvisioner::class);
        $context = $this->createExecutionContext(['provisioner' => $provisioner]);

        $this->assertSame($provisioner, $context->getProvisioner());
    }

    public function testGetTeam(): void
    {
        $team = Team::fromArray(['id' => 456, 'name' => 'team', 'owner' => ['id' => 1, 'name' => 'owner', 'email' => 'email']]);
        $context = $this->createExecutionContext(['team' => $team]);

        $this->assertSame($team, $context->getTeam());
    }

    public function testProvision(): void
    {
        $locator = \Mockery::mock(ServiceLocator::class);
        $provisioner = \Mockery::mock(ResourceProvisioner::class);
        $context = $this->createExecutionContext(['locator' => $locator, 'provisioner' => $provisioner]);

        $resourceClass = 'SomeResource';
        $definition = \Mockery::mock(ProvisionableResourceDefinitionInterface::class);
        $resource = SecretFactory::create();

        $locator->shouldReceive('get')
                ->once()
                ->with($resourceClass)
                ->andReturn($definition);

        $provisioner->shouldReceive('provision')
                    ->once()
                    ->with($definition, $context, [])
                    ->andReturn($resource);

        $this->assertSame($resource, $context->provision($resourceClass));
    }

    public function testProvisionThrowsExceptionWhenNotProvisionable(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('doesn\'t implement the "Ymir\Cli\Resource\Definition\ProvisionableResourceDefinitionInterface" interface');

        $locator = \Mockery::mock(ServiceLocator::class);
        $context = $this->createExecutionContext(['locator' => $locator]);

        $resourceClass = 'SomeResource';
        $definition = SecretFactory::create();

        $locator->shouldReceive('get')
                ->once()
                ->with($resourceClass)
                ->andReturn($definition);

        $context->provision($resourceClass);
    }

    public function testResolve(): void
    {
        $locator = \Mockery::mock(ServiceLocator::class);
        $context = $this->createExecutionContext(['locator' => $locator]);

        $resourceClass = 'SomeResource';
        $definition = \Mockery::mock(ResolvableResourceDefinitionInterface::class);
        $resource = SecretFactory::create();

        $locator->shouldReceive('get')
                ->once()
                ->with($resourceClass)
                ->andReturn($definition);

        $definition->shouldReceive('resolve')
                   ->once()
                   ->with($context, 'Which resource?')
                   ->andReturn($resource);

        $this->assertSame($resource, $context->resolve($resourceClass, 'Which resource?'));
    }

    public function testResolvePreparesQuestionWithParentResource(): void
    {
        $locator = \Mockery::mock(ServiceLocator::class);
        $parentResource = SecretFactory::create(['name' => 'parent']);
        $context = $this->createExecutionContext(['locator' => $locator])->withParentResource($parentResource);

        $resourceClass = 'SomeResource';
        $definition = \Mockery::mock(ResolvableResourceDefinitionInterface::class);
        $resource = SecretFactory::create();

        $locator->shouldReceive('get')
                ->once()
                ->with($resourceClass)
                ->andReturn($definition);

        $definition->shouldReceive('resolve')
                   ->once()
                   ->with($context, 'Which resource for parent?')
                   ->andReturn($resource);

        $this->assertSame($resource, $context->resolve($resourceClass, 'Which resource for %s?'));
    }

    public function testResolvePreparesQuestionWithProject(): void
    {
        $locator = \Mockery::mock(ServiceLocator::class);
        $project = ProjectFactory::create(['name' => 'project']);
        $context = $this->createExecutionContext(['locator' => $locator, 'project' => $project]);

        $resourceClass = 'SomeResource';
        $definition = \Mockery::mock(ResolvableResourceDefinitionInterface::class);
        $resource = SecretFactory::create();

        $locator->shouldReceive('get')
                ->once()
                ->with($resourceClass)
                ->andReturn($definition);

        $definition->shouldReceive('resolve')
                   ->once()
                   ->with($context, 'Which resource for project?')
                   ->andReturn($resource);

        $this->assertSame($resource, $context->resolve($resourceClass, 'Which resource for %s?'));
    }

    public function testWithParentResource(): void
    {
        $context = $this->createExecutionContext();
        $parentResource = SecretFactory::create();
        $newContext = $context->withParentResource($parentResource);

        $this->assertNotSame($context, $newContext);
        $this->assertSame($parentResource, $newContext->getParentResource());
        $this->assertNull($context->getParentResource());
    }

    public function testWithProject(): void
    {
        $context = $this->createExecutionContext();
        $project = ProjectFactory::create();
        $newContext = $context->withProject($project);

        $this->assertNotSame($context, $newContext);
        $this->assertSame($project, $newContext->getProject());
        $this->assertNull($context->getProject());
    }

    private function createExecutionContext(array $args = []): ExecutionContext
    {
        return new ExecutionContext(
            $args['apiClient'] ?? \Mockery::mock(ApiClient::class),
            $args['homeDirectory'] ?? '/home/user',
            $args['input'] ?? \Mockery::mock(Input::class),
            $args['locator'] ?? \Mockery::mock(ServiceLocator::class),
            $args['output'] ?? \Mockery::mock(Output::class),
            array_key_exists('project', $args) ? $args['project'] : null,
            $args['projectConfiguration'] ?? \Mockery::mock(ProjectConfiguration::class),
            $args['projectDirectory'] ?? '/path/to/project',
            $args['provisioner'] ?? \Mockery::mock(ResourceProvisioner::class),
            array_key_exists('team', $args) ? $args['team'] : Team::fromArray(['id' => 123, 'name' => 'team', 'owner' => ['id' => 1, 'name' => 'owner', 'email' => 'email']])
        );
    }
}
