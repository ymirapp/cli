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

namespace Ymir\Cli\Tests\Unit\Project;

use Ymir\Cli\Project\EnvironmentConfiguration;
use Ymir\Cli\Tests\TestCase;

class EnvironmentConfigurationTest extends TestCase
{
    public function testGetArchitectureReturnsArchitectureIfPresent(): void
    {
        $this->assertSame('arm64', (new EnvironmentConfiguration('prod', ['architecture' => 'arm64']))->getArchitecture());
    }

    public function testGetArchitectureReturnsEmptyStringIfMissing(): void
    {
        $this->assertSame('', (new EnvironmentConfiguration('prod', []))->getArchitecture());
    }

    public function testGetBuildCommandsReturnsCommandsIfPresent(): void
    {
        $this->assertSame(['npm install', 'npm run build'], (new EnvironmentConfiguration('prod', ['build' => ['commands' => ['npm install', 'npm run build']]]))->getBuildCommands());
    }

    public function testGetBuildCommandsReturnsEmptyArrayIfMissing(): void
    {
        $this->assertSame([], (new EnvironmentConfiguration('prod', []))->getBuildCommands());
    }

    public function testGetBuildCommandsReturnsLegacyCommandsIfPresent(): void
    {
        $this->assertSame(['npm install', 'npm run build'], (new EnvironmentConfiguration('prod', ['build' => ['npm install', 'npm run build']]))->getBuildCommands());
    }

    public function testGetBuildIncludePathsReturnsEmptyArrayIfMissing(): void
    {
        $this->assertSame([], (new EnvironmentConfiguration('prod', []))->getBuildIncludePaths());
    }

    public function testGetBuildIncludePathsReturnsPathsIfPresent(): void
    {
        $this->assertSame(['foo', 'bar'], (new EnvironmentConfiguration('prod', ['build' => ['include' => ['foo', 'bar']]]))->getBuildIncludePaths());
    }

    public function testGetConsoleTimeoutReturnsDefaultValue(): void
    {
        $this->assertSame(60, (new EnvironmentConfiguration('prod', []))->getConsoleTimeout());
    }

    public function testGetConsoleTimeoutReturnsValueIfPresent(): void
    {
        $this->assertSame(120, (new EnvironmentConfiguration('prod', ['console' => ['timeout' => 120]]))->getConsoleTimeout());
    }

    public function testGetDatabaseServerNameReturnsNameIfArray(): void
    {
        $this->assertSame('foo', (new EnvironmentConfiguration('prod', ['database' => ['server' => 'foo']]))->getDatabaseServerName());
    }

    public function testGetDatabaseServerNameReturnsNameIfString(): void
    {
        $this->assertSame('foo', (new EnvironmentConfiguration('prod', ['database' => 'foo']))->getDatabaseServerName());
    }

    public function testGetDatabaseServerNameReturnsNullIfMissing(): void
    {
        $this->assertNull((new EnvironmentConfiguration('prod', []))->getDatabaseServerName());
    }

    public function testGetDeploymentTypeReturnsNullIfMissing(): void
    {
        $this->assertNull((new EnvironmentConfiguration('prod', []))->getDeploymentType());
    }

    public function testGetDeploymentTypeReturnsValueIfArray(): void
    {
        $this->assertSame('image', (new EnvironmentConfiguration('prod', ['deployment' => ['type' => 'image']]))->getDeploymentType());
    }

    public function testGetDeploymentTypeReturnsValueIfString(): void
    {
        $this->assertSame('image', (new EnvironmentConfiguration('prod', ['deployment' => 'image']))->getDeploymentType());
    }

    public function testGetDomainsReturnsDomainsIfPresent(): void
    {
        $this->assertSame(['foo.com'], (new EnvironmentConfiguration('prod', ['domain' => ['foo.com']]))->getDomains());
    }

    public function testGetDomainsReturnsEmptyArrayIfMissing(): void
    {
        $this->assertSame([], (new EnvironmentConfiguration('prod', []))->getDomains());
    }

    public function testGetName(): void
    {
        $this->assertSame('prod', (new EnvironmentConfiguration('prod', []))->getName());
    }

    public function testIsImageDeploymentTypeReturnsFalseIfDifferent(): void
    {
        $this->assertFalse((new EnvironmentConfiguration('prod', ['deployment' => 'function']))->isImageDeploymentType());
    }

    public function testIsImageDeploymentTypeReturnsFalseIfMissing(): void
    {
        $this->assertFalse((new EnvironmentConfiguration('prod', []))->isImageDeploymentType());
    }

    public function testIsImageDeploymentTypeReturnsTrueIfImage(): void
    {
        $this->assertTrue((new EnvironmentConfiguration('prod', ['deployment' => 'image']))->isImageDeploymentType());
    }

    public function testWithMergesConfiguration(): void
    {
        $configuration = new EnvironmentConfiguration('prod', ['foo' => 'bar']);
        $newConfiguration = $configuration->with(['bar' => 'baz']);

        $this->assertNotSame($configuration, $newConfiguration);
        $this->assertSame('prod', $newConfiguration->getName());
        $this->assertSame(['foo' => 'bar', 'bar' => 'baz'], $newConfiguration->toArray());
    }

    public function testWithoutRemovesKeys(): void
    {
        $configuration = new EnvironmentConfiguration('prod', ['foo' => 'bar', 'bar' => 'baz']);
        $newConfiguration = $configuration->without('foo');

        $this->assertNotSame($configuration, $newConfiguration);
        $this->assertSame('prod', $newConfiguration->getName());
        $this->assertSame(['bar' => 'baz'], $newConfiguration->toArray());
    }
}
