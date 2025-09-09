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

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Ymir\Cli\Exception\SystemException;
use Ymir\Cli\GitHubClient;
use Ymir\Cli\Tests\TestCase;

class GitHubClientTest extends TestCase
{
    public function testDownloadLatestVersionThrowsExceptionIfNoZipballUrl(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionMessage('Unable to parse the WordPress plugin versions from the GitHub API');

        $client = \Mockery::mock(ClientInterface::class);
        $client->shouldReceive('request')->once()
               ->andReturn(new Response(200, [], json_encode([['name' => 'v1.0.0']]))); // No zipball_url

        (new GitHubClient($client))->downloadLatestVersion('foo/bar');
    }

    public function testGetTagsReturnsCollection(): void
    {
        $client = \Mockery::mock(ClientInterface::class);
        $client->shouldReceive('request')->once()
               ->andReturn(new Response(200, [], json_encode([['name' => 'v1.0.0']])));

        $tags = (new GitHubClient($client))->getTags('foo/bar');

        $this->assertCount(1, $tags);
        $this->assertSame('v1.0.0', $tags[0]['name']);
    }

    public function testGetTagsThrowsExceptionOnInvalidJson(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionMessage('Failed to decode response from the GitHub API');

        $client = \Mockery::mock(ClientInterface::class);
        $client->shouldReceive('request')->once()
               ->andReturn(new Response(200, [], 'invalid json'));

        (new GitHubClient($client))->getTags('foo/bar');
    }

    public function testGetTagsThrowsExceptionOnNon200Response(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionMessage('Unable to get the tags for the "foo/bar" repository from the GitHub API');

        $client = \Mockery::mock(ClientInterface::class);
        $client->shouldReceive('request')->once()
               ->with($this->identicalTo('GET'), $this->identicalTo('https://api.github.com/repos/foo/bar/tags'))
               ->andReturn(new Response(404));

        (new GitHubClient($client))->getTags('foo/bar');
    }
}
