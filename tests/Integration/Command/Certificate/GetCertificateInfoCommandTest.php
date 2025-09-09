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

use Ymir\Cli\Command\Certificate\GetCertificateInfoCommand;
use Ymir\Cli\Resource\Definition\CertificateDefinition;
use Ymir\Cli\Resource\Model\Certificate;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\CertificateFactory;
use Ymir\Cli\Tests\Integration\Command\TestCase;

class GetCertificateInfoCommandTest extends TestCase
{
    public function testGetCertificateInfoSuccessfully(): void
    {
        $team = $this->setupActiveTeam();

        $certificate = CertificateFactory::create([
            'id' => 10,
            'region' => 'us-east-1',
            'status' => 'available',
            'in_use' => false,
            'domains' => [
                ['domain_name' => 'example.com', 'validated' => true, 'managed' => false, 'validation_record' => ['name' => '_abc.example.com', 'value' => '_def.acm-validations.aws']],
            ],
            'provider' => ['id' => 1, 'name' => 'AWS'],
        ]);

        $this->apiClient->shouldReceive('getTeam')->with(1)->andReturn($team);
        $this->apiClient->shouldReceive('getCertificates')->with($team)->andReturn(new ResourceCollection([$certificate]));

        $contextFactory = $this->createExecutionContextFactory([
            Certificate::class => function () { return new CertificateDefinition(); },
        ]);

        $this->bootApplication([new GetCertificateInfoCommand($this->apiClient, $contextFactory)]);
        $tester = $this->executeCommand(GetCertificateInfoCommand::NAME, ['certificate' => '10']);

        $this->assertStringContainsString('example.com', $tester->getDisplay());
        $this->assertStringContainsString('validated', $tester->getDisplay());
        $this->assertStringContainsString('AWS', $tester->getDisplay());
        $this->assertStringContainsString('us-east-1', $tester->getDisplay());
        $this->assertStringContainsString('available', $tester->getDisplay());
        $this->assertStringContainsString('no', $tester->getDisplay());
    }
}
