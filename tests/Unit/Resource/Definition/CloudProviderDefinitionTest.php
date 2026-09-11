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

namespace Ymir\Cli\Tests\Unit\Resource\Definition;

use Illuminate\Support\Enumerable;
use Ymir\Cli\ApiClient;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Resource\NoResourcesFoundException;
use Ymir\Cli\Exception\Resource\ResourceNotFoundException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Definition\CloudProviderDefinition;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Resource\Requirement\ActiveTeamRequirement;
use Ymir\Cli\Resource\Requirement\AwsCredentialsRequirement;
use Ymir\Cli\Resource\Requirement\NameRequirement;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\CloudProviderFactory;
use Ymir\Cli\Tests\Factory\ProjectFactory;
use Ymir\Cli\Tests\Factory\TeamFactory;
use Ymir\Cli\Tests\TestCase;

class CloudProviderDefinitionTest extends TestCase
{
    /**
     * @var ApiClient|\Mockery\MockInterface
     */
    private $apiClient;

    /**
     * @var ExecutionContext|\Mockery\MockInterface
     */
    private $context;

    /**
     * @var Input|\Mockery\MockInterface
     */
    private $input;

    /**
     * @var \Mockery\MockInterface|Output
     */
    private $output;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->apiClient = \Mockery::mock(ApiClient::class);
        $this->context = \Mockery::mock(ExecutionContext::class);
        $this->input = \Mockery::mock(Input::class);
        $this->output = \Mockery::mock(Output::class);

        $this->context->shouldReceive('getApiClient')->andReturn($this->apiClient);
        $this->context->shouldReceive('getInput')->andReturn($this->input);
        $this->context->shouldReceive('getOutput')->andReturn($this->output);
        $this->context->shouldReceive('getTeam')->andReturn(TeamFactory::create());
    }

    public static function provideProviderSelections(): array
    {
        $selections = [];

        foreach (['argument', 'option', 'project'] as $source) {
            foreach (['pending', 'connected', 'disconnected'] as $status) {
                foreach ([true, false] as $requiresConnectedProvider) {
                    $selections[] = [$source, $status, $requiresConnectedProvider];
                }
            }
        }

        return $selections;
    }

    public function testGetRequirements(): void
    {
        $definition = new CloudProviderDefinition();
        $requirements = $definition->getRequirements();

        $this->assertCount(3, $requirements);
        $this->assertInstanceOf(ActiveTeamRequirement::class, $requirements['active_team']);
        $this->assertInstanceOf(NameRequirement::class, $requirements['name']);
        $this->assertInstanceOf(AwsCredentialsRequirement::class, $requirements['credentials']);
    }

    public function testProvision(): void
    {
        $cloudProvider = CloudProviderFactory::create();

        $this->apiClient->shouldReceive('createProvider')->once()
                  ->with($cloudProvider->getTeam(), 'name', ['key' => 'value'])
                  ->andReturn($cloudProvider);

        $definition = new CloudProviderDefinition();

        $this->assertSame($cloudProvider, $definition->provision($this->apiClient, [
            'active_team' => $cloudProvider->getTeam(),
            'name' => 'name',
            'credentials' => ['key' => 'value'],
        ]));
    }

    public function testResolveDoesNotRetainProviderStatusRequirement(): void
    {
        $connectedProvider = CloudProviderFactory::create(['id' => 123]);
        $disconnectedProvider = CloudProviderFactory::create(['id' => 456, 'status' => 'disconnected']);

        $this->input->shouldReceive('hasArgument')->with('provider')->twice()->andReturn(true);
        $this->input->shouldReceive('getNumericArgument')->with('provider')->twice()->andReturn(123, 456);
        $this->apiClient->shouldReceive('getProviders')->twice()->andReturn(
            new ResourceCollection([$connectedProvider]),
            new ResourceCollection([$disconnectedProvider])
        );

        $definition = new CloudProviderDefinition();

        $this->assertSame($connectedProvider, $definition->resolve($this->context, 'question', ['status' => 'connected']));
        $this->assertSame($disconnectedProvider, $definition->resolve($this->context, 'question'));
    }

    public function testResolveFiltersInteractiveChoicesByStatusOnly(): void
    {
        $providers = new ResourceCollection([
            CloudProviderFactory::create(['id' => 1, 'name' => 'Pending', 'status' => 'pending']),
            CloudProviderFactory::create(['id' => 2, 'name' => 'Keys', 'authentication' => ['method' => 'access_key']]),
            CloudProviderFactory::create(['id' => 3, 'name' => 'Role', 'authentication' => ['method' => 'assume_role']]),
            CloudProviderFactory::create(['id' => 4, 'name' => 'Disconnected', 'status' => 'disconnected', 'authentication' => ['method' => 'assume_role']]),
        ]);
        $this->input->shouldReceive('hasArgument')->with('provider')->andReturn(false);
        $this->input->shouldReceive('hasOption')->with('provider')->andReturn(false);
        $this->apiClient->shouldReceive('getProviders')->andReturn($providers);
        $this->context->shouldReceive('getProject')->andReturn(null);
        $this->output->shouldReceive('choiceWithId')->once()->with('question', \Mockery::on(function (Enumerable $choices): bool {
            return [2 => 'Keys', 3 => 'Role'] === $choices->all();
        }))->andReturn(3);

        $this->assertSame($providers->firstWhereId(3), (new CloudProviderDefinition())->resolve($this->context, 'question', ['status' => 'connected']));
    }

    public function testResolveReturnsProviderFromProjectIfNoIdProvided(): void
    {
        $cloudProvider = CloudProviderFactory::create();
        $project = ProjectFactory::create();

        $this->input->shouldReceive('hasArgument')->with('provider')->andReturn(false);
        $this->input->shouldReceive('hasOption')->with('provider')->andReturn(false);
        $this->apiClient->shouldReceive('getProviders')->andReturn(new ResourceCollection([$cloudProvider]));
        $this->context->shouldReceive('getProject')->andReturn($project);

        $definition = new CloudProviderDefinition();

        $this->assertSame($project->getProvider(), $definition->resolve($this->context, 'question'));
    }

    public function testResolveThrowsExceptionIfNoConnectedProvidersFound(): void
    {
        $this->input->shouldReceive('hasArgument')->with('provider')->andReturn(false);
        $this->input->shouldReceive('hasOption')->with('provider')->andReturn(false);
        $this->apiClient->shouldReceive('getProviders')->andReturn(new ResourceCollection([
            CloudProviderFactory::create(['status' => 'pending']),
            CloudProviderFactory::create(['id' => 2, 'status' => 'disconnected']),
        ]));
        $this->context->shouldReceive('getProject')->andReturn(null);
        $this->output->shouldNotReceive('choiceWithId');

        $this->expectException(NoResourcesFoundException::class);
        $this->expectExceptionMessage('The currently active team has no connected cloud provider connections, but you can inspect their status with the "provider:list" command');

        (new CloudProviderDefinition())->resolve($this->context, 'question', ['status' => 'connected']);
    }

    public function testResolveThrowsExceptionIfNoProvidersFound(): void
    {
        $this->input->shouldReceive('hasArgument')->with('provider')->andReturn(false);
        $this->input->shouldReceive('hasOption')->with('provider')->andReturn(false);
        $this->apiClient->shouldReceive('getProviders')->andReturn(new ResourceCollection([]));

        $this->expectException(NoResourcesFoundException::class);
        $this->expectExceptionMessage('The currently active team has no cloud providers, but you can connect one with the "provider:connect" command');

        $definition = new CloudProviderDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfProviderNotFoundAfterChoice(): void
    {
        $this->input->shouldReceive('hasArgument')->with('provider')->andReturn(false);
        $this->input->shouldReceive('hasOption')->with('provider')->andReturn(false);
        $this->apiClient->shouldReceive('getProviders')->andReturn(new ResourceCollection([CloudProviderFactory::create()]));
        $this->context->shouldReceive('getProject')->andReturn(null);
        $this->output->shouldReceive('choiceWithId')->with('question', \Mockery::type(Enumerable::class))->andReturn(123);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Unable to find a cloud provider with "123" as the ID or name');

        $definition = new CloudProviderDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfProviderNotFoundWhenIdProvided(): void
    {
        $this->input->shouldReceive('hasArgument')->with('provider')->andReturn(true);
        $this->input->shouldReceive('getNumericArgument')->with('provider')->andReturn(123);
        $this->apiClient->shouldReceive('getProviders')->andReturn(new ResourceCollection([CloudProviderFactory::create(['id' => 456])]));

        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('The given provider "123" isn\'t available to the currently active team');

        $definition = new CloudProviderDefinition();
        $definition->resolve($this->context, 'question');
    }

    /**
     * @dataProvider provideProviderSelections
     */
    public function testResolveValidatesSelectedProvider(string $source, string $status, bool $requiresConnectedProvider): void
    {
        $provider = CloudProviderFactory::create(['id' => 123, 'status' => $status]);
        $this->input->shouldReceive('hasArgument')->with('provider')->andReturn('argument' === $source);
        $this->input->shouldReceive('hasOption')->with('provider')->andReturn('option' === $source);
        $this->input->shouldReceive('getNumericArgument')->with('provider')->andReturn(123);
        $this->input->shouldReceive('getNumericOption')->with('provider')->andReturn(123);
        $this->apiClient->shouldReceive('getProviders')->once()->with($this->context->getTeam())->andReturn(new ResourceCollection([
            $provider,
            CloudProviderFactory::create(['id' => 456]),
        ]));
        $this->output->shouldNotReceive('choiceWithId');

        if ('project' === $source) {
            $project = new Project(1, 'project', 'us-east-1', $provider);
            $this->context->shouldReceive('getProject')->andReturn($project);
        } else {
            $this->context->shouldNotReceive('getProject');
        }

        if ($requiresConnectedProvider && 'connected' !== $status) {
            $this->expectException(InvalidInputException::class);
            $this->expectExceptionMessage(sprintf('The "name" cloud provider connection (ID: 123) cannot be used for operations because its status is "%s"', $status));
        }

        $fulfilledRequirements = $requiresConnectedProvider ? ['status' => 'connected'] : [];

        $this->assertSame($provider, (new CloudProviderDefinition())->resolve($this->context, 'question', $fulfilledRequirements));
    }

    public function testResolveWithArgument(): void
    {
        $cloudProvider = CloudProviderFactory::create(['id' => 123]);

        $this->input->shouldReceive('hasArgument')->with('provider')->andReturn(true);
        $this->input->shouldReceive('getNumericArgument')->with('provider')->andReturn(123);
        $this->apiClient->shouldReceive('getProviders')->andReturn(new ResourceCollection([$cloudProvider]));

        $definition = new CloudProviderDefinition();

        $this->assertSame($cloudProvider, $definition->resolve($this->context, 'question'));
    }

    public function testResolveWithChoice(): void
    {
        $cloudProvider = CloudProviderFactory::create(['id' => 123, 'name' => 'choice-provider']);

        $this->input->shouldReceive('hasArgument')->with('provider')->andReturn(false);
        $this->input->shouldReceive('hasOption')->with('provider')->andReturn(false);
        $this->apiClient->shouldReceive('getProviders')->andReturn(new ResourceCollection([$cloudProvider]));
        $this->context->shouldReceive('getProject')->andReturn(null);
        $this->output->shouldReceive('choiceWithId')->with('question', \Mockery::type(Enumerable::class))->andReturn(123);

        $definition = new CloudProviderDefinition();

        $this->assertSame($cloudProvider, $definition->resolve($this->context, 'question'));
    }

    public function testResolveWithOption(): void
    {
        $cloudProvider = CloudProviderFactory::create(['id' => 123]);

        $this->input->shouldReceive('hasArgument')->with('provider')->andReturn(false);
        $this->input->shouldReceive('hasOption')->with('provider')->andReturn(true);
        $this->input->shouldReceive('getNumericOption')->with('provider')->andReturn(123);
        $this->apiClient->shouldReceive('getProviders')->andReturn(new ResourceCollection([$cloudProvider]));

        $definition = new CloudProviderDefinition();

        $this->assertSame($cloudProvider, $definition->resolve($this->context, 'question'));
    }
}
