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

use Ymir\Cli\Command\Certificate\DeleteCertificateCommand;
use Ymir\Cli\Resource\Definition\CertificateDefinition;
use Ymir\Cli\Resource\Model\Certificate;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\CertificateFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class DeleteCertificateCommandTest extends TestCase
{
    public function testDeleteCertificateCancelled(): void
    {
        $team = $this->setupActiveTeam();

        $certificate = CertificateFactory::create([
            'id' => 10,
            'provider' => ['id' => 1, 'name' => 'AWS'],
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getCertificates')->with($team)->andReturn(new ResourceCollection([$certificate]));
        $this->apiClient->shouldNotReceive('deleteCertificate');

        $contextFactory = $this->createExecutionContextFactory([
            Certificate::class => function () { return new CertificateDefinition(); },
        ]);

        $this->bootApplication([new DeleteCertificateCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(DeleteCertificateCommand::NAME, ['certificate' => '10'], ['n']);

        $this->assertStringNotContainsString('SSL certificate deleted', $tester->getDisplay());
    }

    public function testDeleteCertificateSuccessfully(): void
    {
        $team = $this->setupActiveTeam();

        $certificate = CertificateFactory::create([
            'id' => 10,
            'provider' => ['id' => 1, 'name' => 'AWS'],
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getCertificates')->with($team)->andReturn(new ResourceCollection([$certificate]));
        $this->apiClient->shouldReceive('deleteCertificate')->once()->with($certificate);

        $contextFactory = $this->createExecutionContextFactory([
            Certificate::class => function () { return new CertificateDefinition(); },
        ]);

        $this->bootApplication([new DeleteCertificateCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(DeleteCertificateCommand::NAME, ['certificate' => '10'], ['y']);

        $this->assertStringContainsString('SSL certificate deleted', $tester->getDisplay());
    }
}
