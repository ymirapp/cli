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
use Ymir\Cli\Exception\Resource\NoResourcesFoundException;
use Ymir\Cli\Exception\Resource\ResourceNotFoundException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Definition\TeamDefinition;
use Ymir\Cli\Resource\Requirement\NameRequirement;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\TeamFactory;
use Ymir\Cli\Tests\TestCase;

class TeamDefinitionTest extends TestCase
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
    }

    public function testGetRequirements(): void
    {
        $definition = new TeamDefinition();
        $requirements = $definition->getRequirements();

        $this->assertCount(1, $requirements);
        $this->assertInstanceOf(NameRequirement::class, $requirements['name']);
    }

    public function testProvision(): void
    {
        $team = TeamFactory::create();

        $this->apiClient->shouldReceive('createTeam')->once()
                  ->with('name')
                  ->andReturn($team);

        $definition = new TeamDefinition();

        $this->assertSame($team, $definition->provision($this->apiClient, [
            'name' => 'name',
        ]));
    }

    public function testResolveThrowsExceptionIfNoTeamsFound(): void
    {
        $this->input->shouldReceive('hasArgument')->with('team')->andReturn(false);
        $this->input->shouldReceive('hasOption')->with('team')->andReturn(false);
        $this->apiClient->shouldReceive('getTeams')->andReturn(new ResourceCollection([]));

        $this->expectException(NoResourcesFoundException::class);
        $this->expectExceptionMessage('You are not a member of any teams, but you can create one with the "team:create" command');

        $definition = new TeamDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfTeamNotFound(): void
    {
        $this->input->shouldReceive('hasArgument')->with('team')->andReturn(true);
        $this->input->shouldReceive('getNumericArgument')->with('team')->andReturn(123);
        $this->apiClient->shouldReceive('getTeams')->andReturn(new ResourceCollection([TeamFactory::create(['id' => 456])]));

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Unable to find a team with "123" as the ID or name');

        $definition = new TeamDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveWithArgument(): void
    {
        $team = TeamFactory::create(['id' => 123]);

        $this->input->shouldReceive('hasArgument')->with('team')->andReturn(true);
        $this->input->shouldReceive('getNumericArgument')->with('team')->andReturn(123);
        $this->apiClient->shouldReceive('getTeams')->andReturn(new ResourceCollection([$team]));

        $definition = new TeamDefinition();

        $this->assertSame($team, $definition->resolve($this->context, 'question'));
    }

    public function testResolveWithChoice(): void
    {
        $team = TeamFactory::create(['id' => 123, 'name' => 'choice-team']);

        $this->input->shouldReceive('hasArgument')->with('team')->andReturn(false);
        $this->input->shouldReceive('hasOption')->with('team')->andReturn(false);
        $this->apiClient->shouldReceive('getTeams')->andReturn(new ResourceCollection([$team]));
        $this->output->shouldReceive('choiceWithId')->with('question', \Mockery::type(Enumerable::class))->andReturn(123);

        $definition = new TeamDefinition();

        $this->assertSame($team, $definition->resolve($this->context, 'question'));
    }

    public function testResolveWithOption(): void
    {
        $team = TeamFactory::create(['id' => 123]);

        $this->input->shouldReceive('hasArgument')->with('team')->andReturn(false);
        $this->input->shouldReceive('hasOption')->with('team')->andReturn(true);
        $this->input->shouldReceive('getNumericOption')->with('team')->andReturn(123);
        $this->apiClient->shouldReceive('getTeams')->andReturn(new ResourceCollection([$team]));

        $definition = new TeamDefinition();

        $this->assertSame($team, $definition->resolve($this->context, 'question'));
    }
}
