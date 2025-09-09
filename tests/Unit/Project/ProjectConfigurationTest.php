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

use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\Exception\ConfigurationException;
use Ymir\Cli\Project\ProjectConfiguration;
use Ymir\Cli\Tests\TestCase;

class ProjectConfigurationTest extends TestCase
{
    /**
     * @var string
     */
    private $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'ymir-test');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testExistsReturnsFalseIfFileDoesNotExist(): void
    {
        $this->assertFalse((new ProjectConfiguration(new Filesystem(), [], 'non-existent-file'))->exists());
    }

    public function testExistsReturnsTrueIfFileExists(): void
    {
        $this->assertTrue((new ProjectConfiguration(new Filesystem(), [], $this->tempFile))->exists());
    }

    public function testGetProjectIdReturnsId(): void
    {
        file_put_contents($this->tempFile, 'id: 123');

        $this->assertSame(123, (new ProjectConfiguration(new Filesystem(), [], $this->tempFile))->getProjectId());
    }

    public function testGetProjectIdThrowsExceptionIfIdIsMissing(): void
    {
        file_put_contents($this->tempFile, 'name: foo');

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('No "id" found in Ymir project configuration file');

        (new ProjectConfiguration(new Filesystem(), [], $this->tempFile))->getProjectId();
    }

    public function testGetProjectNameReturnsName(): void
    {
        file_put_contents($this->tempFile, 'name: foo');

        $this->assertSame('foo', (new ProjectConfiguration(new Filesystem(), [], $this->tempFile))->getProjectName());
    }

    public function testGetProjectNameThrowsExceptionIfNameIsMissing(): void
    {
        file_put_contents($this->tempFile, 'id: 123');

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('No "name" found in Ymir project configuration file');

        (new ProjectConfiguration(new Filesystem(), [], $this->tempFile))->getProjectName();
    }

    public function testGetProjectTypeThrowsExceptionIfTypeIsMissing(): void
    {
        file_put_contents($this->tempFile, 'id: 123');

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('No "type" found in Ymir project configuration file');

        (new ProjectConfiguration(new Filesystem(), [], $this->tempFile))->getProjectType();
    }

    public function testLoadConfigurationThrowsExceptionIfParsingFails(): void
    {
        file_put_contents($this->tempFile, 'invalid yaml: [');

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Error parsing Ymir project configuration file');

        new ProjectConfiguration(new Filesystem(), [], $this->tempFile);
    }
}
