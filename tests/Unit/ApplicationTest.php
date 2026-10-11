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

namespace Ymir\Cli\Tests\Unit;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\ApplicationTester;
use Ymir\Cli\Application;
use Ymir\Cli\Exception\CommandCancelledException;
use Ymir\Cli\Exception\Project\DeploymentCancelledException;
use Ymir\Cli\Exception\RuntimeException;
use Ymir\Cli\Exception\WarningExceptionInterface;
use Ymir\Cli\Tests\TestCase;

class ApplicationTest extends TestCase
{
    public function testRunRendersDeploymentCancelledExceptionAsComment(): void
    {
        $tester = $this->runCommandThrowing(new DeploymentCancelledException('Deployment <info>cancelled</info>'), OutputInterface::VERBOSITY_VERBOSE);

        $this->assertSame(130, $tester->getStatusCode());
        $this->assertSame("\033[33mDeployment <info>cancelled</info>\033[39m".PHP_EOL, $tester->getDisplay());
    }

    public function testRunRendersDeploymentCancelledExceptionWhenQuiet(): void
    {
        $tester = $this->runCommandThrowing(new DeploymentCancelledException('Deployment cancelled'), OutputInterface::VERBOSITY_QUIET);

        $this->assertSame(130, $tester->getStatusCode());
        $this->assertSame("\033[33mDeployment cancelled\033[39m".PHP_EOL, $tester->getDisplay());
    }

    public function testRunRendersOtherExceptions(): void
    {
        $tester = $this->runCommandThrowing(new RuntimeException('Deployment failed'), OutputInterface::VERBOSITY_VERBOSE);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('[Ymir\Cli\Exception\RuntimeException]', $tester->getDisplay());
        $this->assertStringContainsString('Deployment failed', $tester->getDisplay());
        $this->assertStringContainsString('Exception trace:', $tester->getDisplay());
    }

    public function testRunRendersWarningExceptionAsComment(): void
    {
        $tester = $this->runCommandThrowing(new class('Backup <info>skipped</info>', 3) extends RuntimeException implements WarningExceptionInterface {
        }, OutputInterface::VERBOSITY_QUIET);

        $this->assertSame(3, $tester->getStatusCode());
        $this->assertSame("\033[33mBackup <info>skipped</info>\033[39m".PHP_EOL, $tester->getDisplay());
    }

    public function testRunReturnsDeploymentCancelledExceptionCode(): void
    {
        $tester = $this->runCommandThrowing(new DeploymentCancelledException('Deployment cancelled'), OutputInterface::VERBOSITY_NORMAL);

        $this->assertSame(130, $tester->getStatusCode());
        $this->assertSame("\033[33mDeployment cancelled\033[39m".PHP_EOL, $tester->getDisplay());
    }

    public function testRunSilencesCommandCancelledException(): void
    {
        $tester = $this->runCommandThrowing(new CommandCancelledException('Cancelled'), OutputInterface::VERBOSITY_VERBOSE);

        $this->assertSame(130, $tester->getStatusCode());
        $this->assertSame('', $tester->getDisplay());
    }

    private function runCommandThrowing(\Throwable $exception, int $verbosity): ApplicationTester
    {
        $application = new Application([], 'test');
        $application->setAutoExit(false);
        $application->register('fail')->setCode(function () use ($exception): int {
            throw $exception;
        });

        $tester = new ApplicationTester($application);
        $tester->run(['command' => 'fail'], ['decorated' => true, 'verbosity' => $verbosity]);

        return $tester;
    }
}
