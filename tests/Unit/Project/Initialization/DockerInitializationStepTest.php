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
use Ymir\Cli\Dockerfile;
use Ymir\Cli\Executable\DockerExecutable;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Project\Configuration\ImageDeploymentConfigurationChange;
use Ymir\Cli\Project\Initialization\DockerInitializationStep;
use Ymir\Cli\Tests\TestCase;

class DockerInitializationStepTest extends TestCase
{
    /**
     * @var ExecutionContext|\Mockery\MockInterface
     */
    private $context;
    /**
     * @var DockerExecutable|\Mockery\MockInterface
     */
    private $dockerExecutable;

    /**
     * @var Dockerfile|\Mockery\MockInterface
     */
    private $dockerfile;

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

        $this->dockerExecutable = \Mockery::mock(DockerExecutable::class);
        $this->dockerfile = \Mockery::mock(Dockerfile::class);
        $this->context = \Mockery::mock(ExecutionContext::class);
        $this->output = \Mockery::mock(Output::class);

        $this->context->shouldReceive('getOutput')->andReturn($this->output);
    }

    public function testPerformAsksToOverwriteExistingDockerfile(): void
    {
        $this->output->shouldReceive('confirm')->with('Do you want to deploy this project using a container image?')->once()->andReturn(true);
        $this->dockerfile->shouldReceive('exists')->once()->andReturn(true);
        $this->output->shouldReceive('confirm')->with('A <comment>Dockerfile</comment> already exists in the project directory. Do you want to overwrite it?', false)->once()->andReturn(true);
        $this->dockerfile->shouldReceive('create')->once();
        $this->dockerExecutable->shouldReceive('isInstalled')->once()->andReturn(true);

        $step = new DockerInitializationStep($this->dockerExecutable, $this->dockerfile);

        $result = $step->perform($this->context, []);

        $this->assertInstanceOf(ImageDeploymentConfigurationChange::class, $result);
    }

    public function testPerformCreatesDockerfileAndReturnsImageDeploymentConfigurationChange(): void
    {
        $this->output->shouldReceive('confirm')->with('Do you want to deploy this project using a container image?')->once()->andReturn(true);
        $this->dockerfile->shouldReceive('exists')->once()->andReturn(false);
        $this->dockerfile->shouldReceive('create')->once();
        $this->dockerExecutable->shouldReceive('isInstalled')->once()->andReturn(true);

        $step = new DockerInitializationStep($this->dockerExecutable, $this->dockerfile);

        $result = $step->perform($this->context, []);

        $this->assertInstanceOf(ImageDeploymentConfigurationChange::class, $result);
    }

    public function testPerformDoesNotOverwriteExistingDockerfileIfUserDeclines(): void
    {
        $this->output->shouldReceive('confirm')->with('Do you want to deploy this project using a container image?')->once()->andReturn(true);
        $this->dockerfile->shouldReceive('exists')->once()->andReturn(true);
        $this->output->shouldReceive('confirm')->with('A <comment>Dockerfile</comment> already exists in the project directory. Do you want to overwrite it?', false)->once()->andReturn(false);
        $this->dockerfile->shouldNotReceive('create');
        $this->dockerExecutable->shouldReceive('isInstalled')->once()->andReturn(true);

        $step = new DockerInitializationStep($this->dockerExecutable, $this->dockerfile);

        $result = $step->perform($this->context, []);

        $this->assertInstanceOf(ImageDeploymentConfigurationChange::class, $result);
    }

    public function testPerformReturnsNullIfUserDeclinesImageDeployment(): void
    {
        $this->output->shouldReceive('confirm')->with('Do you want to deploy this project using a container image?')->once()->andReturn(false);

        $step = new DockerInitializationStep($this->dockerExecutable, $this->dockerfile);

        $this->assertNull($step->perform($this->context, []));
    }

    public function testPerformShowsWarningIfDockerIsNotInstalled(): void
    {
        $this->output->shouldReceive('confirm')->with('Do you want to deploy this project using a container image?')->once()->andReturn(true);
        $this->dockerfile->shouldReceive('exists')->once()->andReturn(false);
        $this->dockerfile->shouldReceive('create')->once();
        $this->dockerExecutable->shouldReceive('isInstalled')->once()->andReturn(false);
        $this->output->shouldReceive('warning')->with("<comment>Docker</comment> wasn't detected and is required to deploy the project locally")->once();

        $step = new DockerInitializationStep($this->dockerExecutable, $this->dockerfile);

        $result = $step->perform($this->context, []);

        $this->assertInstanceOf(ImageDeploymentConfigurationChange::class, $result);
    }
}
