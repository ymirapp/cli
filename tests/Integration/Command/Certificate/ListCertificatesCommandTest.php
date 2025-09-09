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

use Ymir\Cli\Command\Certificate\ListCertificatesCommand;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\CertificateFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class ListCertificatesCommandTest extends TestCase
{
    public function testListCertificatesSuccessfully(): void
    {
        $team = $this->setupActiveTeam();

        $certificate = CertificateFactory::create([
            'id' => 10,
            'region' => 'us-east-1',
            'status' => 'available',
            'in_use' => true,
            'domains' => [
                ['domain_name' => 'example.com'],
                ['domain_name' => 'www.example.com'],
            ],
            'provider' => ['id' => 1, 'name' => 'AWS'],
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getCertificates')->with($team)->andReturn(new ResourceCollection([$certificate]));

        $this->bootApplication([new ListCertificatesCommand($this->apiClient, $this->createExecutionContextFactory())]);
        $tester = $this->executeCommand(ListCertificatesCommand::NAME);

        $this->assertStringContainsString('10', $tester->getDisplay());
        $this->assertStringContainsString('AWS', $tester->getDisplay());
        $this->assertStringContainsString('us-east-1', $tester->getDisplay());
        $this->assertStringContainsString('example.com', $tester->getDisplay());
        $this->assertStringContainsString('www.example.com', $tester->getDisplay());
        $this->assertStringContainsString('available', $tester->getDisplay());
        $this->assertStringContainsString('yes', $tester->getDisplay());
    }
}
