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

use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Tests\TestCase;

class CliConfigurationTest extends TestCase
{
    /**
     * @var string
     */
    private $tempFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempFile = tempnam(sys_get_temp_dir(), 'ymir-cli-config');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }

        parent::tearDown();
    }

    public function testGetAccessTokenFromEnvironment(): void
    {
        putenv('YMIR_API_TOKEN=env-token');
        $config = new CliConfiguration($this->tempFile, new Filesystem());
        $this->assertSame('env-token', $config->getAccessToken());
        putenv('YMIR_API_TOKEN');
    }

    public function testGetAccessTokenFromFile(): void
    {
        file_put_contents($this->tempFile, json_encode(['token' => 'file-token']));
        $config = new CliConfiguration($this->tempFile, new Filesystem());
        $this->assertSame('file-token', $config->getAccessToken());
    }

    public function testGetActiveTeamIdReturnsId(): void
    {
        file_put_contents($this->tempFile, json_encode(['active_team' => 123]));
        $config = new CliConfiguration($this->tempFile, new Filesystem());
        $this->assertSame(123, $config->getActiveTeamId());
    }

    public function testGetActiveTeamIdReturnsNullWhenMissing(): void
    {
        $config = new CliConfiguration($this->tempFile, new Filesystem());
        $this->assertNull($config->getActiveTeamId());
    }

    public function testGitHubCliVersion(): void
    {
        $config = new CliConfiguration($this->tempFile, new Filesystem());
        $config->setGitHubCliVersion('1.2.3');
        $this->assertSame('1.2.3', $config->getGitHubCliVersion());
    }

    public function testGitHubLastCheckedTimestamp(): void
    {
        $timestamp = time();
        $config = new CliConfiguration($this->tempFile, new Filesystem());
        $config->setGitHubLastCheckedTimestamp($timestamp);
        $this->assertSame($timestamp, $config->getGitHubLastCheckedTimestamp());
    }

    public function testSavesToFileOnDestruct(): void
    {
        $filesystem = \Mockery::mock(Filesystem::class);
        $filesystem->shouldReceive('exists')
                   ->andReturn(false);
        $filesystem->shouldReceive('dumpFile')
                   ->once()
                   ->with($this->tempFile, \Mockery::pattern('/"token": "save-token"/'));

        $config = new CliConfiguration($this->tempFile, $filesystem);
        $config->setAccessToken('save-token');
        unset($config);
    }

    public function testSetAccessToken(): void
    {
        $config = new CliConfiguration($this->tempFile, new Filesystem());
        $config->setAccessToken('new-token');
        $this->assertSame('new-token', $config->getAccessToken());
    }

    public function testSetActiveTeamId(): void
    {
        $config = new CliConfiguration($this->tempFile, new Filesystem());
        $config->setActiveTeamId(456);
        $this->assertSame(456, $config->getActiveTeamId());
    }
}
