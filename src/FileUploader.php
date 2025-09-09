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

namespace Ymir\Cli;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Enumerable;
use Symfony\Component\Console\Helper\ProgressBar;
use Ymir\Cli\Exception\SystemException;

class FileUploader
{
    /**
     * The default headers to send with our requests.
     *
     * @var array
     */
    private const DEFAULT_HEADERS = ['Cache-Control' => 'public, max-age=2628000'];

    /**
     * The HTTP client used to upload files.
     *
     * @var ClientInterface
     */
    private $client;

    /**
     * Constructor.
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Sends multiple requests concurrently.
     */
    public function batch(string $method, Enumerable $requests, ?ProgressBar $progressBar = null): void
    {
        if ($progressBar instanceof ProgressBar) {
            $progressBar->start(count($requests));
        }

        $requests = function () use ($method, $requests, $progressBar) {
            foreach ($requests as $request) {
                if ($progressBar instanceof ProgressBar) {
                    $progressBar->advance();
                }

                yield new Request($method, $request['uri'], array_merge(self::DEFAULT_HEADERS, $request['headers']), $request['body'] ?? null);
            }
        };

        $pool = new Pool($this->client, $requests());
        $pool->promise()->wait();

        if ($progressBar instanceof ProgressBar) {
            $progressBar->finish();
        }
    }

    /**
     * Upload the given file to the given URL.
     */
    public function uploadFile(string $filePath, string $url, array $headers = [], ?ProgressBar $progressBar = null): void
    {
        if (!is_readable($filePath)) {
            throw new SystemException(sprintf('Cannot read the "%s" file', $filePath));
        }

        $progressCallback = null;
        $file = fopen($filePath, 'r+');

        if (!is_resource($file)) {
            throw new SystemException(sprintf('Cannot open the "%s" file', $filePath));
        }

        if ($progressBar instanceof ProgressBar) {
            $progressBar->start((int) round(filesize($filePath) / 1024));

            $progressCallback = function ($_, $__, $___, $uploaded) use ($progressBar): void {
                $progressBar->setProgress((int) round($uploaded / 1024));
            };
        }

        $this->retry(function () use ($file, $headers, $progressCallback, $url): void {
            $this->client->request('PUT', $url, array_filter([
                'body' => $file,
                'headers' => array_merge(self::DEFAULT_HEADERS, $headers),
                'progress' => $progressCallback,
            ]));
        });

        if ($progressBar instanceof ProgressBar) {
            $progressBar->finish();
        }

        if (is_resource($file)) {
            fclose($file);
        }
    }

    /**
     * Retry callback a given number of times.
     */
    private function retry(callable $callback, $times = 5)
    {
        beginning:
        $times--;

        try {
            return $callback();
        } catch (\Throwable $exception) {
            if ($times < 1) {
                throw $exception;
            }

            sleep(1);

            goto beginning;
        }
    }
}
