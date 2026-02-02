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

namespace Ymir\Cli\Tests\Unit\Project\Initialization;

use Ymir\Cli\ApiClient;
use Ymir\Cli\Console\Output;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Project\Configuration\DatabaseConfigurationChange;
use Ymir\Cli\Project\Initialization\DatabaseInitializationStep;
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\ResourceCollection;
use Ymir\Cli\Tests\Factory\DatabaseFactory;
use Ymir\Cli\Tests\Factory\DatabaseServerFactory;
use Ymir\Cli\Tests\Factory\TeamFactory;
use Ymir\Cli\Tests\TestCase;

class DatabaseInitializationStepTest extends TestCase
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
        $this->output = \Mockery::mock(Output::class);

        $this->context->shouldReceive('getApiClient')->andReturn($this->apiClient);
        $this->context->shouldReceive('getOutput')->andReturn($this->output);
        $this->context->shouldReceive('getTeam')->andReturn(TeamFactory::create());
    }

    public function testPerformProvisionsNewServerIfRequested(): void
    {
        $databaseServer = DatabaseServerFactory::create([
            'name' => 'new-db-server',
            'publicly_accessible' => false,
        ]);

        $this->apiClient->shouldReceive('getDatabaseServers')->once()->andReturn(new ResourceCollection([]));
        $this->output->shouldReceive('confirm')->with(\Mockery::pattern('/Your team doesn\'t have any configured database servers/'))->once()->andReturn(true);
        $this->context->shouldReceive('provision')->with(DatabaseServer::class, ['region' => 'us-east-1'])->once()->andReturn($databaseServer);
        $this->output->shouldReceive('askSlug')->once()->andReturn('prefix_');
        $this->output->shouldReceive('warning')->once();

        $step = new DatabaseInitializationStep();

        $result = $step->perform($this->context, [
            'region' => 'us-east-1',
            'name' => 'project',
            'environments' => ['staging'],
        ]);

        $this->assertInstanceOf(DatabaseConfigurationChange::class, $result);
    }

    public function testPerformReturnsDatabaseConfigurationChangeIfServerSelected(): void
    {
        $databaseServer = DatabaseServerFactory::create([
            'name' => 'db-server',
            'region' => 'us-east-1',
            'status' => 'available',
            'publicly_accessible' => true,
        ]);

        $this->apiClient->shouldReceive('getDatabaseServers')->once()->andReturn(new ResourceCollection([$databaseServer]));
        $this->output->shouldReceive('confirm')->with('Would you like to use an existing database server for this project?')->once()->andReturn(true);
        $this->output->shouldReceive('choiceWithResourceDetails')->once()->andReturn('db-server');
        $this->output->shouldReceive('askSlug')->once()->andReturn('prefix_');
        $this->output->shouldReceive('confirm')->with(\Mockery::pattern('/Would you like to create the/'))->once()->andReturn(true);
        $this->apiClient->shouldReceive('createDatabase')->once()->with($databaseServer, 'prefix_staging')->andReturn(DatabaseFactory::create());

        $step = new DatabaseInitializationStep();

        $result = $step->perform($this->context, [
            'region' => 'us-east-1',
            'name' => 'project',
            'environments' => ['staging'],
        ]);

        $this->assertInstanceOf(DatabaseConfigurationChange::class, $result);
    }

    public function testPerformReturnsNullIfNoDatabaseServerSelected(): void
    {
        $this->apiClient->shouldReceive('getDatabaseServers')->once()->andReturn(new ResourceCollection([]));
        $this->output->shouldReceive('confirm')->once()->andReturn(false);

        $step = new DatabaseInitializationStep();

        $this->assertNull($step->perform($this->context, ['region' => 'us-east-1']));
    }
}
