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
use Ymir\Cli\Resource\Definition\EmailIdentityDefinition;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\EmailIdentityFactory;
use Ymir\Cli\Tests\Factory\TeamFactory;
use Ymir\Cli\Tests\TestCase;

class EmailIdentityDefinitionTest extends TestCase
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

    public function testResolveThrowsExceptionIfEmailIdentityNotFound(): void
    {
        $this->input->shouldReceive('getStringArgument')->with('identity')->andReturn('non-existent');
        $this->apiClient->shouldReceive('getEmailIdentities')->andReturn(new ResourceCollection([EmailIdentityFactory::create(['name' => 'other'])]));

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Unable to find a email identity with "non-existent" as the ID or name');

        $definition = new EmailIdentityDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfIdentityIdOrNameIsEmptyAfterChoice(): void
    {
        $this->input->shouldReceive('getStringArgument')->with('identity')->andReturn('');
        $this->input->shouldReceive('getStringOption')->with('identity', true)->andReturn('');
        $this->apiClient->shouldReceive('getEmailIdentities')->andReturn(new ResourceCollection([EmailIdentityFactory::create()]));
        $this->output->shouldReceive('choice')->andReturn('');

        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('You must provide a valid email identity ID or name');

        $definition = new EmailIdentityDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfNoEmailIdentitiesFound(): void
    {
        $this->input->shouldReceive('getStringArgument')->with('identity')->andReturn('');
        $this->input->shouldReceive('getStringOption')->with('identity', true)->andReturn('');
        $this->apiClient->shouldReceive('getEmailIdentities')->andReturn(new ResourceCollection([]));

        $this->expectException(NoResourcesFoundException::class);
        $this->expectExceptionMessage('The currently active team has no email identities, but you can create one with the "email:identity:create" command');

        $definition = new EmailIdentityDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveWithArgument(): void
    {
        $emailIdentity = EmailIdentityFactory::create(['name' => 'my-identity']);

        $this->input->shouldReceive('getStringArgument')->with('identity')->andReturn('my-identity');
        $this->apiClient->shouldReceive('getEmailIdentities')->andReturn(new ResourceCollection([$emailIdentity]));

        $definition = new EmailIdentityDefinition();

        $this->assertSame($emailIdentity, $definition->resolve($this->context, 'question'));
    }

    public function testResolveWithChoice(): void
    {
        $emailIdentity = EmailIdentityFactory::create(['name' => 'choice-identity']);

        $this->input->shouldReceive('getStringArgument')->with('identity')->andReturn('');
        $this->input->shouldReceive('getStringOption')->with('identity', true)->andReturn('');
        $this->apiClient->shouldReceive('getEmailIdentities')->andReturn(new ResourceCollection([$emailIdentity]));
        $this->output->shouldReceive('choice')->with('question', \Mockery::type(Enumerable::class))->andReturn('choice-identity');

        $definition = new EmailIdentityDefinition();

        $this->assertSame($emailIdentity, $definition->resolve($this->context, 'question'));
    }

    public function testResolveWithOption(): void
    {
        $emailIdentity = EmailIdentityFactory::create(['id' => 123]);

        $this->input->shouldReceive('getStringArgument')->with('identity')->andReturn('');
        $this->input->shouldReceive('getStringOption')->with('identity', true)->andReturn('123');
        $this->apiClient->shouldReceive('getEmailIdentities')->andReturn(new ResourceCollection([$emailIdentity]));

        $definition = new EmailIdentityDefinition();

        $this->assertSame($emailIdentity, $definition->resolve($this->context, 'question'));
    }
}
