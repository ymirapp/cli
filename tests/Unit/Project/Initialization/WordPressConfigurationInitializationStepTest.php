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
use Ymir\Cli\Executable\WpCliExecutable;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Project\Configuration\AggregateConfigurationChange;
use Ymir\Cli\Project\Configuration\WordPress\WordPressConfigurationChangeInterface;
use Ymir\Cli\Project\Initialization\WordPressConfigurationInitializationStep;
use Ymir\Cli\Tests\TestCase;

class WordPressConfigurationInitializationStepTest extends TestCase
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
     * @var \Mockery\MockInterface|WpCliExecutable
     */
    private $wpCliExecutable;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->context = \Mockery::mock(ExecutionContext::class);
        $this->output = \Mockery::mock(Output::class);
        $this->wpCliExecutable = \Mockery::mock(WpCliExecutable::class);

        $this->context->shouldReceive('getOutput')->andReturn($this->output);
    }

    public function testPerformReturnsAggregateChangeIfPluginsDetected(): void
    {
        $this->wpCliExecutable->shouldReceive('isInstalled')->once()->andReturn(true);
        $this->wpCliExecutable->shouldReceive('isWordPressInstalled')->once()->andReturn(true);
        $this->output->shouldReceive('confirm')->andReturn(true);
        $this->wpCliExecutable->shouldReceive('listPlugins')->once()->andReturn(collect([
            ['name' => 'plugin1', 'status' => 'active', 'title' => 'Plugin 1'],
            ['name' => 'plugin2', 'status' => 'must-use', 'title' => 'Plugin 2'],
        ]));
        $this->output->shouldReceive('info')->once()->with('Applying configuration changes for the following <comment>active</comment> plugins:');
        $this->output->shouldReceive('list')->once();

        $change1 = \Mockery::mock(WordPressConfigurationChangeInterface::class);
        $change1->shouldReceive('getName')->andReturn('plugin1');
        $change2 = \Mockery::mock(WordPressConfigurationChangeInterface::class);
        $change2->shouldReceive('getName')->andReturn('plugin2');

        $step = new WordPressConfigurationInitializationStep($this->wpCliExecutable, [$change1, $change2]);

        $result = $step->perform($this->context, []);

        $this->assertInstanceOf(AggregateConfigurationChange::class, $result);
    }

    public function testPerformReturnsNullIfNoPluginsDetected(): void
    {
        $this->wpCliExecutable->shouldReceive('isInstalled')->once()->andReturn(true);
        $this->wpCliExecutable->shouldReceive('isWordPressInstalled')->once()->andReturn(true);
        $this->output->shouldReceive('confirm')->andReturn(true);
        $this->wpCliExecutable->shouldReceive('listPlugins')->once()->andReturn(collect([
            ['name' => 'plugin1', 'status' => 'inactive'],
        ]));
        $this->output->shouldReceive('info')->once()->with('No plugins or themes requiring configuration were detected');

        $change = \Mockery::mock(WordPressConfigurationChangeInterface::class);
        $change->shouldReceive('getName')->andReturn('plugin2');

        $step = new WordPressConfigurationInitializationStep($this->wpCliExecutable, [$change]);

        $this->assertNull($step->perform($this->context, []));
    }

    public function testPerformReturnsNullIfUserDeclinesScan(): void
    {
        $this->wpCliExecutable->shouldReceive('isInstalled')->once()->andReturn(true);
        $this->wpCliExecutable->shouldReceive('isWordPressInstalled')->once()->andReturn(true);
        $this->output->shouldReceive('confirm')->once()->with('Do you want to have Ymir scan your plugins and themes and configure your project?')->andReturn(false);

        $step = new WordPressConfigurationInitializationStep($this->wpCliExecutable);

        $this->assertNull($step->perform($this->context, []));
    }

    public function testPerformReturnsNullIfWordPressNotInstalled(): void
    {
        $this->wpCliExecutable->shouldReceive('isInstalled')->once()->andReturn(true);
        $this->wpCliExecutable->shouldReceive('isWordPressInstalled')->once()->andReturn(false);

        $step = new WordPressConfigurationInitializationStep($this->wpCliExecutable);

        $this->assertNull($step->perform($this->context, []));
    }

    public function testPerformReturnsNullIfWpCliNotInstalled(): void
    {
        $this->wpCliExecutable->shouldReceive('isInstalled')->once()->andReturn(false);

        $step = new WordPressConfigurationInitializationStep($this->wpCliExecutable);

        $this->assertNull($step->perform($this->context, []));
    }
}
