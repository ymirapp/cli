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

use Ymir\Cli\ApiClient;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Resource\NoResourcesFoundException;
use Ymir\Cli\Exception\Resource\ResourceNotFoundException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Definition\CertificateDefinition;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\CertificateFactory;
use Ymir\Cli\Tests\Factory\TeamFactory;
use Ymir\Cli\Tests\TestCase;

class CertificateDefinitionTest extends TestCase
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

    public function testResolveThrowsExceptionIfCertificateIdIsEmptyAfterChoice(): void
    {
        $this->input->shouldReceive('getStringArgument')->with('certificate')->andReturn('');
        $this->input->shouldReceive('getStringOption')->with('certificate', true)->andReturn('');
        $this->apiClient->shouldReceive('getCertificates')->andReturn(new ResourceCollection([CertificateFactory::create()]));
        $this->output->shouldReceive('choice')->andReturn('');

        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('You must provide a valid SSL certificate ID');

        $definition = new CertificateDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfCertificateNotFound(): void
    {
        $this->input->shouldReceive('getStringArgument')->with('certificate')->andReturn('123');
        $this->apiClient->shouldReceive('getCertificates')->andReturn(new ResourceCollection([CertificateFactory::create(['id' => 456])]));

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Unable to find a SSL certificate with "123" as the ID or name');

        $definition = new CertificateDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveThrowsExceptionIfNoCertificatesFound(): void
    {
        $this->input->shouldReceive('getStringArgument')->with('certificate')->andReturn('');
        $this->input->shouldReceive('getStringOption')->with('certificate', true)->andReturn('');
        $this->apiClient->shouldReceive('getCertificates')->andReturn(new ResourceCollection([]));

        $this->expectException(NoResourcesFoundException::class);
        $this->expectExceptionMessage('The currently active team has no SSL certificates, but you can request one with the "certificate:request" command');

        $definition = new CertificateDefinition();
        $definition->resolve($this->context, 'question');
    }

    public function testResolveWithArgument(): void
    {
        $certificate = CertificateFactory::create(['id' => 123]);

        $this->input->shouldReceive('getStringArgument')->with('certificate')->andReturn('123');
        $this->apiClient->shouldReceive('getCertificates')->andReturn(new ResourceCollection([$certificate]));

        $definition = new CertificateDefinition();

        $this->assertSame($certificate, $definition->resolve($this->context, 'question'));
    }

    public function testResolveWithChoice(): void
    {
        $certificate = CertificateFactory::create(['id' => 123, 'region' => 'us-east-1', 'domains' => [['domain_name' => 'example.com']]]);

        $this->input->shouldReceive('getStringArgument')->with('certificate')->andReturn('');
        $this->input->shouldReceive('getStringOption')->with('certificate', true)->andReturn('');
        $this->apiClient->shouldReceive('getCertificates')->andReturn(new ResourceCollection([$certificate]));
        $this->output->shouldReceive('choice')->with('question', [123 => '123: example.com (us-east-1)'])->andReturn('123');

        $definition = new CertificateDefinition();

        $this->assertSame($certificate, $definition->resolve($this->context, 'question'));
    }
}
