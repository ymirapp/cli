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

use Ymir\Cli\Command\Certificate\RequestCertificateCommand;
use Ymir\Cli\Resource\Definition\CloudProviderDefinition;
use Ymir\Cli\Resource\Model\CloudProvider;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\CertificateFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class RequestCertificateCommandTest extends TestCase
{
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
            CloudProvider::class => function () { return new CloudProviderDefinition(); },
        ]);

        $this->bootApplication([new RequestCertificateCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(RequestCertificateCommand::NAME, ['domains' => ['*.example.com']], ['1', 'us-east-1']);

        $this->assertStringContainsString('SSL certificate requested', $tester->getDisplay());
    }
}
