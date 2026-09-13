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

namespace Ymir\Cli\Tests\Integration\Command\Certificate;

use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Console\Tester\CommandTester;
use Ymir\Cli\Command\Certificate\RequestCertificateCommand;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Resource\Definition\CertificateDefinition;
use Ymir\Cli\Resource\Definition\CloudProviderDefinition;
use Ymir\Cli\Resource\Model\Certificate;
use Ymir\Cli\Resource\Model\CloudProvider;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\CertificateFactory;
use Ymir\Cli\Tests\Factory\CloudProviderFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class RequestCertificateCommandTest extends TestCase
{
    public function testRejectsExplicitDisconnectedProviderWithoutInteraction(): void
    {
        $team = $this->setupActiveTeam();
        $provider = CloudProviderFactory::create(['id' => 123, 'status' => 'disconnected']);

        $this->apiClient->shouldReceive('getProviders')->once()->with($team)->andReturn(new ResourceCollection([$provider]));
        $this->apiClient->shouldNotReceive('createCertificate');
        $this->apiClient->shouldNotReceive('getRegions');

        $this->bootApplication([new RequestCertificateCommand($this->apiClient, $this->createExecutionContextFactory([
            Certificate::class => function () { return new CertificateDefinition(); },
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]))]);
        $tester = new CommandTester($this->application->find(RequestCertificateCommand::NAME));

        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('The "name" cloud provider connection (ID: 123) cannot be used for operations because its status is "disconnected"');

        $tester->execute(['domains' => ['*.example.com'], '--region' => 'us-east-1', '--provider' => '123'], ['interactive' => false]);
    }

    public function testRequestCertificateSuccessfully(): void
    {
        $project = $this->setupValidProject();
        $team = $this->setupActiveTeam();

        $provider = $project->getProvider();
        $certificate = CertificateFactory::create([
            'id' => 10,
            'domains' => [['domain_name' => 'example.com', 'managed' => false, 'validation_record' => ['name' => '_abc', 'value' => '_def']]],
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getProviders')->with($team)->andReturn(new ResourceCollection([$provider]));
        $this->apiClient->shouldReceive('getRegions')->with($provider)->andReturn(collect(['us-east-1' => 'US East (N. Virginia)']));
        $this->apiClient->shouldReceive('createCertificate')->once()->with($provider, ['example.com', '*.example.com'], 'us-east-1')->andReturn($certificate);
        $this->apiClient->shouldReceive('getCertificate')->with(10)->andReturn($certificate);

        $contextFactory = $this->createExecutionContextFactory([
            Certificate::class => function () { return new CertificateDefinition(); },
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]);

        $this->bootApplication([new RequestCertificateCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(RequestCertificateCommand::NAME, ['domains' => ['example.com']], ['y', '1', 'us-east-1']);

        $this->assertStringContainsString('SSL certificate requested', $tester->getDisplay());
        $this->assertStringContainsString('_abc', $tester->getDisplay());
        $this->assertStringContainsString('_def', $tester->getDisplay());
    }

    public function testRequestCertificateWithNoDomainsArgumentSuccessfully(): void
    {
        $project = $this->setupValidProject();
        $team = $this->setupActiveTeam();

        $provider = $project->getProvider();
        $certificate = CertificateFactory::create([
            'id' => 10,
            'domains' => [['domain_name' => 'example.com', 'managed' => true]],
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getProviders')->with($team)->andReturn(new ResourceCollection([$provider]));
        $this->apiClient->shouldReceive('getRegions')->with($provider)->andReturn(collect(['us-east-1' => 'US East (N. Virginia)']));
        $this->apiClient->shouldReceive('createCertificate')->once()->with($provider, ['example.com', '*.example.com'], 'us-east-1')->andReturn($certificate);

        $contextFactory = $this->createExecutionContextFactory([
            Certificate::class => function () { return new CertificateDefinition(); },
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]);

        $this->bootApplication([new RequestCertificateCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(RequestCertificateCommand::NAME, [], ['example.com', 'y', '1', 'us-east-1']);

        $this->assertStringContainsString('SSL certificate requested', $tester->getDisplay());
    }

    public function testRequestCertificateWithNoWildcardSuccessfully(): void
    {
        $project = $this->setupValidProject();
        $team = $this->setupActiveTeam();

        $provider = $project->getProvider();
        $certificate = CertificateFactory::create([
            'id' => 10,
            'domains' => [['domain_name' => 'example.com', 'managed' => true]],
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getProviders')->with($team)->andReturn(new ResourceCollection([$provider]));
        $this->apiClient->shouldReceive('getRegions')->with($provider)->andReturn(collect(['us-east-1' => 'US East (N. Virginia)']));
        $this->apiClient->shouldReceive('createCertificate')->once()->with($provider, ['example.com'], 'us-east-1')->andReturn($certificate);

        $contextFactory = $this->createExecutionContextFactory([
            Certificate::class => function () { return new CertificateDefinition(); },
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]);

        $this->bootApplication([new RequestCertificateCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(RequestCertificateCommand::NAME, ['domains' => ['example.com']], ['n', '1', 'us-east-1']);

        $this->assertStringContainsString('SSL certificate requested', $tester->getDisplay());
    }

    public function testRequestCertificateWithWildcardDomainSuccessfully(): void
    {
        $project = $this->setupValidProject();
        $team = $this->setupActiveTeam();

        $provider = $project->getProvider();
        $certificate = CertificateFactory::create([
            'id' => 10,
            'domains' => [['domain_name' => '*.example.com', 'managed' => true]],
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getProviders')->with($team)->andReturn(new ResourceCollection([$provider]));
        $this->apiClient->shouldReceive('getRegions')->with($provider)->andReturn(collect(['us-east-1' => 'US East (N. Virginia)']));
        $this->apiClient->shouldReceive('createCertificate')->once()->with($provider, ['*.example.com', 'example.com'], 'us-east-1')->andReturn($certificate);

        $contextFactory = $this->createExecutionContextFactory([
            Certificate::class => function () { return new CertificateDefinition(); },
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]);

        $this->bootApplication([new RequestCertificateCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(RequestCertificateCommand::NAME, ['domains' => ['*.example.com']], ['1', 'us-east-1']);

        $this->assertStringContainsString('SSL certificate requested', $tester->getDisplay());
    }

    public function testRetriesFetchingValidationRecordsWithoutRequestingAnotherCertificate(): void
    {
        $team = $this->setupActiveTeam();
        $provider = CloudProviderFactory::create(['id' => 1]);
        $certificate = CertificateFactory::create([
            'id' => 10,
            'domains' => [['domain_name' => '*.example.com', 'managed' => false]],
        ]);
        $validatedCertificate = CertificateFactory::create([
            'id' => 10,
            'domains' => [['domain_name' => '*.example.com', 'managed' => false, 'validation_record' => ['name' => '_abc', 'value' => '_def']]],
        ]);
        $exception = new ServerException('Unable to fetch the SSL certificate', new Request('GET', '/certificates/10'), new Response(503));

        $this->apiClient->shouldReceive('getProviders')->once()->with($team)->andReturn(new ResourceCollection([$provider]));
        $this->apiClient->shouldReceive('getRegions')->once()->with($provider)->andReturn(collect(['us-east-1' => 'US East (N. Virginia)']));
        $this->apiClient->shouldReceive('createCertificate')->once()->with($provider, ['*.example.com', 'example.com'], 'us-east-1')->andReturn($certificate)->ordered();
        $this->apiClient->shouldReceive('getCertificate')->once()->with(10)->andThrow($exception)->ordered();
        $this->apiClient->shouldReceive('getCertificate')->once()->with(10)->andReturn($validatedCertificate)->ordered();

        $this->bootApplication([new RequestCertificateCommand($this->apiClient, $this->createExecutionContextFactory([
            Certificate::class => function () { return new CertificateDefinition(); },
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]))]);
        $tester = $this->executeCommand(RequestCertificateCommand::NAME, [
            'domains' => ['*.example.com'],
            '--provider' => '1',
            '--region' => 'us-east-1',
        ], ['yes']);

        $this->assertStringContainsString('Failed to fetch the DNS validation records for the SSL certificate (ID: 10)', $tester->getDisplay());
        $this->assertStringContainsString('Failed to finalize the SSL certificate. Do you want to retry?', $tester->getDisplay());
        $this->assertStringContainsString('SSL certificate requested', $tester->getDisplay());
        $this->assertStringContainsString('_abc', $tester->getDisplay());
    }
}
