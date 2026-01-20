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
use Ymir\Cli\Resource\Definition\DnsZoneDefinition;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DnsZoneFactory;
use Ymir\Cli\Tests\Factory\TeamFactory;
use Ymir\Cli\Tests\TestCase;

class DnsZoneDefinitionTest extends TestCase
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

    public function testResolveThrowsExceptionIfDnsZoneNotFound(): void
    {
        $this->input->shouldReceive('getStringArgument')->with('zone')->andReturn('non-existent');
        $this->apiClient->shouldReceive('getDnsZones')->andReturn(new ResourceCollection([DnsZoneFactory::create(['domain_name' => 'other'])]));

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Unable to find a DNS zone with "non-existent" as the ID or name');

        $definition = new DnsZoneDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfNoDnsZonesFound(): void
    {
        $this->input->shouldReceive('getStringArgument')->with('zone')->andReturn('');
        $this->input->shouldReceive('getStringOption')->with('zone', true)->andReturn('');
        $this->apiClient->shouldReceive('getDnsZones')->andReturn(new ResourceCollection([]));

        $this->expectException(NoResourcesFoundException::class);
        $this->expectExceptionMessage('The currently active team has no DNS zones, but you can create one with the "dns:zone:create" command');

        $definition = new DnsZoneDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfZoneIdOrNameIsEmptyAfterChoice(): void
    {
        $this->input->shouldReceive('getStringArgument')->with('zone')->andReturn('');
        $this->input->shouldReceive('getStringOption')->with('zone', true)->andReturn('');
        $this->apiClient->shouldReceive('getDnsZones')->andReturn(new ResourceCollection([DnsZoneFactory::create()]));
        $this->output->shouldReceive('choice')->andReturn('');

        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('You must provide a valid DNS zone ID or name');

        $definition = new DnsZoneDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveWithArgument(): void
    {
        $dnsZone = DnsZoneFactory::create(['domain_name' => 'my-zone']);

        $this->input->shouldReceive('getStringArgument')->with('zone')->andReturn('my-zone');
        $this->apiClient->shouldReceive('getDnsZones')->andReturn(new ResourceCollection([$dnsZone]));

        $definition = new DnsZoneDefinition();

        $this->assertSame($dnsZone, $definition->resolve($this->context, 'question'));
    }

    public function testResolveWithChoice(): void
    {
        $dnsZone = DnsZoneFactory::create(['domain_name' => 'choice-zone']);

        $this->input->shouldReceive('getStringArgument')->with('zone')->andReturn('');
        $this->input->shouldReceive('getStringOption')->with('zone', true)->andReturn('');
        $this->apiClient->shouldReceive('getDnsZones')->andReturn(new ResourceCollection([$dnsZone]));
        $this->output->shouldReceive('choice')->with('question', \Mockery::type(Enumerable::class))->andReturn('choice-zone');

        $definition = new DnsZoneDefinition();

        $this->assertSame($dnsZone, $definition->resolve($this->context, 'question'));
    }
}
