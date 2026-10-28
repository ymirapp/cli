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

namespace Ymir\Cli\Tests\Unit\Project\Deployment;

use Carbon\Carbon;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Dockerfile;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Project\Deployment\WarnEndOfLifePhpVersionStep;
use Ymir\Cli\Resource\Model\Deployment;
use Ymir\Cli\Tests\Factory\DeploymentFactory;
use Ymir\Cli\Tests\Factory\EnvironmentFactory;
use Ymir\Cli\Tests\TestCase;

class WarnEndOfLifePhpVersionStepTest extends TestCase
{
    /**
     * @var string
     */
    private $defaultTimezone;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultTimezone = date_default_timezone_get();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        date_default_timezone_set($this->defaultTimezone);

        parent::tearDown();
    }

    public static function provideSilentConfigurations(): array
    {
        return [
            'supported php' => [['php' => '8.3'], 'deployment'],
            'unknown php' => [['php' => '9.9'], 'deployment'],
            'missing php' => [[], 'deployment'],
            'non-string php' => [['php' => 7.4], 'deployment'],
            'custom layer ignores php' => [['php' => '7.4', 'layers' => ['arn:aws:lambda:us-east-1:123456789012:layer:php:1']], 'deployment'],
            'supported php layer ignores php' => [['php' => '7.4', 'layers' => ['ymir:php-8.3']], 'deployment'],
            'multiple php layers' => [['layers' => ['ymir:php-7.4', 'ymir:php-8.3']], 'deployment'],
            'image redeployment' => [['php' => '7.4', 'deployment' => 'image'], 'redeployment'],
            'image rollback' => [['php' => '7.4', 'deployment' => ['type' => 'image']], 'rollback'],
        ];
    }

    public function testPerformDoesNotWarnIfEnvironmentConfigurationIsMissing(): void
    {
        $context = \Mockery::mock(ExecutionContext::class);
        $dockerfile = \Mockery::mock(Dockerfile::class);

        $context->shouldNotReceive('getOutput');
        $dockerfile->shouldNotReceive('getPhpVersion');

        (new WarnEndOfLifePhpVersionStep($dockerfile))->perform($context, DeploymentFactory::create(['configuration' => ['environments' => ['staging' => ['php' => '7.4']]]]), EnvironmentFactory::create(['name' => 'production']));
    }

    /**
     * @dataProvider provideSilentConfigurations
     */
    public function testPerformDoesNotWarnIfPhpVersionIsSupportedOrUnknown(array $configuration, string $type): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-28'));

        $context = \Mockery::mock(ExecutionContext::class);
        $dockerfile = \Mockery::mock(Dockerfile::class);

        $context->shouldNotReceive('getOutput');
        $dockerfile->shouldNotReceive('getPhpVersion');

        (new WarnEndOfLifePhpVersionStep($dockerfile))->perform($context, $this->createDeployment($configuration, $type), EnvironmentFactory::create(['name' => 'production']));
    }

    public function testPerformDoesNotWarnOnLastDayOfSecuritySupport(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-12-31 23:59:59', 'UTC'));

        $context = \Mockery::mock(ExecutionContext::class);

        $context->shouldNotReceive('getOutput');

        (new WarnEndOfLifePhpVersionStep(\Mockery::mock(Dockerfile::class)))->perform($context, $this->createDeployment(['php' => '8.1']), EnvironmentFactory::create(['name' => 'production']));
    }

    public function testPerformUsesUtcForSecuritySupportBoundary(): void
    {
        $silentContext = \Mockery::mock(ExecutionContext::class);
        $warningContext = \Mockery::mock(ExecutionContext::class);
        $output = \Mockery::mock(Output::class);
        $step = new WarnEndOfLifePhpVersionStep(\Mockery::mock(Dockerfile::class));

        $silentContext->shouldNotReceive('getOutput');
        $warningContext->shouldReceive('getOutput')->andReturn($output);
        $output->shouldReceive('warning')->once()->with(\Mockery::pattern('/PHP <comment>8\.1<\/comment>/'));
        $output->shouldReceive('newLine')->once();

        date_default_timezone_set('Pacific/Kiritimati');
        Carbon::setTestNow(Carbon::parse('2025-12-31 23:59:59', 'UTC'));

        $step->perform($silentContext, $this->createDeployment(['php' => '8.1']), EnvironmentFactory::create(['name' => 'production']));

        date_default_timezone_set('America/Los_Angeles');
        Carbon::setTestNow(Carbon::parse('2026-01-01 00:00:00', 'UTC'));

        $step->perform($warningContext, $this->createDeployment(['php' => '8.1']), EnvironmentFactory::create(['name' => 'production']));
    }

    public function testPerformWarnsForImageDeploymentDockerfile(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-28'));

        $context = \Mockery::mock(ExecutionContext::class);
        $dockerfile = \Mockery::mock(Dockerfile::class);
        $output = \Mockery::mock(Output::class);

        $context->shouldReceive('getOutput')->andReturn($output);
        $dockerfile->shouldReceive('getPhpVersion')->once()->with('production')->andReturn('7.4');
        $output->shouldReceive('warning')->once()->with(\Mockery::pattern('/PHP <comment>7\.4<\/comment> which reached its upstream end of life on 2022-11-28/'));
        $output->shouldReceive('newLine')->once();

        (new WarnEndOfLifePhpVersionStep($dockerfile))->perform($context, $this->createDeployment(['php' => '8.3', 'deployment' => 'image'], 'deployment'), EnvironmentFactory::create(['name' => 'production']));
    }

    public function testPerformWarnsForPhpLayer(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-28'));

        $context = \Mockery::mock(ExecutionContext::class);
        $output = \Mockery::mock(Output::class);

        $context->shouldReceive('getOutput')->andReturn($output);
        $output->shouldReceive('warning')->once()->with(\Mockery::pattern('/PHP <comment>8\.0<\/comment>/'));
        $output->shouldReceive('newLine')->once();

        (new WarnEndOfLifePhpVersionStep(\Mockery::mock(Dockerfile::class)))->perform($context, $this->createDeployment(['php' => '8.3', 'layers' => ['ymir:php-8.0', 'arn:aws:lambda:us-east-1:123456789012:layer:extension:1']], 'rollback'), EnvironmentFactory::create(['name' => 'production']));
    }

    public function testPerformWarnsOnFirstDayAfterSecuritySupport(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 00:00:00', 'UTC'));

        $context = \Mockery::mock(ExecutionContext::class);
        $output = \Mockery::mock(Output::class);

        $context->shouldReceive('getOutput')->andReturn($output);
        $output->shouldReceive('warning')->once()->with('The "<comment>production</comment>" environment uses PHP <comment>8.1</comment> which reached its upstream end of life on 2025-12-31. Security backports from Ymir or other vendors don\'t change its upstream support status, so you should upgrade to a PHP version that is still supported upstream: https://www.php.net/supported-versions.php');
        $output->shouldReceive('newLine')->once();

        (new WarnEndOfLifePhpVersionStep(\Mockery::mock(Dockerfile::class)))->perform($context, $this->createDeployment(['php' => '8.1'], 'redeployment'), EnvironmentFactory::create(['name' => 'production']));
    }

    private function createDeployment(array $configuration, string $type = 'deployment'): Deployment
    {
        return DeploymentFactory::create(['type' => $type, 'configuration' => ['environments' => ['production' => $configuration]]]);
    }
}
