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

use Ymir\Cli\Console\Output;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Project\Initialization\IntegrationInitializationStep;
use Ymir\Cli\Project\Type\ProjectTypeInterface;
use Ymir\Cli\Tests\TestCase;

class IntegrationInitializationStepTest extends TestCase
{
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

        $this->context = \Mockery::mock(ExecutionContext::class);
        $this->output = \Mockery::mock(Output::class);

        $this->context->shouldReceive('getOutput')->andReturn($this->output);
    }

    public function testPerformDoesNotInstallIntegrationIfAlreadyInstalled(): void
    {
        $projectType = \Mockery::mock(ProjectTypeInterface::class);
        $projectDirectory = '/path/to/project';

        $this->context->shouldReceive('getProjectDirectory')->once()->andReturn($projectDirectory);
        $projectType->shouldReceive('isIntegrationInstalled')->with($projectDirectory)->once()->andReturn(true);
        $projectType->shouldNotReceive('installIntegration');

        $step = new IntegrationInitializationStep();

        $result = $step->perform($this->context, ['type' => $projectType]);

        $this->assertNull($result);
    }

    public function testPerformDoesNotInstallIntegrationIfUserDeclines(): void
    {
        $projectType = \Mockery::mock(ProjectTypeInterface::class);
        $projectDirectory = '/path/to/project';

        $this->context->shouldReceive('getProjectDirectory')->once()->andReturn($projectDirectory);
        $projectType->shouldReceive('isIntegrationInstalled')->with($projectDirectory)->once()->andReturn(false);
        $projectType->shouldReceive('getName')->andReturn('WordPress');
        $this->output->shouldReceive('confirm')->with('Would you like to install the Ymir integration for <comment>WordPress</comment>?')->once()->andReturn(false);
        $projectType->shouldNotReceive('installIntegration');

        $step = new IntegrationInitializationStep();

        $result = $step->perform($this->context, ['type' => $projectType]);

        $this->assertNull($result);
    }

    public function testPerformInstallsIntegrationIfRequested(): void
    {
        $projectType = \Mockery::mock(ProjectTypeInterface::class);
        $projectDirectory = '/path/to/project';

        $this->context->shouldReceive('getProjectDirectory')->once()->andReturn($projectDirectory);
        $projectType->shouldReceive('isIntegrationInstalled')->with($projectDirectory)->once()->andReturn(false);
        $projectType->shouldReceive('getName')->andReturn('WordPress');
        $this->output->shouldReceive('confirm')->with('Would you like to install the Ymir integration for <comment>WordPress</comment>?')->once()->andReturn(true);
        $projectType->shouldReceive('installIntegration')->with($projectDirectory)->once();
        $this->output->shouldReceive('info')->with('Ymir <comment>WordPress</comment> integration installed')->once();

        $step = new IntegrationInitializationStep();

        $result = $step->perform($this->context, ['type' => $projectType]);

        $this->assertNull($result);
    }

    public function testPerformReturnsNullIfNoProjectTypeProvided(): void
    {
        $step = new IntegrationInitializationStep();

        $this->assertNull($step->perform($this->context, []));
        $this->assertNull($step->perform($this->context, ['type' => 'not-a-project-type']));
    }
}
