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

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Ymir\Cli\Exception\SystemException;
use Ymir\Cli\FileUploader;
use Ymir\Cli\Tests\TestCase;

class FileUploaderTest extends TestCase
{
    public function testUploadFileHandlesStreamClosedByHttpClient(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ymir-upload-test');
        file_put_contents($tempFile, 'file content');

        $client = new Client(['handler' => function (RequestInterface $request, array $options): PromiseInterface {
            $this->assertSame('file content', $request->getBody()->getContents());
            $request->getBody()->close();

            return Create::promiseFor(new Response(200));
        }]);

        try {
            (new FileUploader($client))->uploadFile($tempFile, 'https://example.com');
        } finally {
            unlink($tempFile);
        }
    }

    public function testUploadFileSendsPutRequest(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ymir-upload-test');
        file_put_contents($tempFile, 'file content');

        $client = \Mockery::mock(ClientInterface::class);
        $client->shouldReceive('request')->once()
               ->with($this->identicalTo('PUT'), $this->identicalTo('https://example.com'), $this->callback(function (array $options): bool {
                   return isset($options['body']) && $options['body'] instanceof StreamInterface && 'file content' === $options['body']->getContents() && 'public, max-age=2628000' === $options['headers']['Cache-Control'];
               }));

        (new FileUploader($client))->uploadFile($tempFile, 'https://example.com');

        unlink($tempFile);
    }

    public function testUploadFileThrowsExceptionIfFileNotReadable(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionMessage('Cannot read the "non-existent-file" file');

        $client = \Mockery::mock(ClientInterface::class);

        (new FileUploader($client))->uploadFile('non-existent-file', 'https://example.com');
    }
}
