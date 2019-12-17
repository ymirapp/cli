<?php

declare(strict_types=1);

/*
 * This file is part of Placeholder command-line tool.
 *
 * (c) Carl Alexander <contact@carlalexander.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Placeholder\Cli;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;

class FileUploader
{
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
    public function batch(string $method, array $requests, array $defaultHeaders = [], ?ProgressBar $progressBar = null)
    {
        if ($progressBar instanceof ProgressBar) {
            $progressBar->start(count($requests));
        }

        $requests = function () use ($method, $requests, $defaultHeaders, $progressBar) {
            foreach ($requests as $request) {
                if ($progressBar instanceof ProgressBar) {
                    $progressBar->advance();
                }

                yield new Request($method, $request['uri'], array_merge($defaultHeaders, $request['headers']));
            }
        };

        $pool = new Pool($this->client, $requests(), ['concurrency' => 10]);
        $pool->promise()->wait();

        if ($progressBar instanceof ProgressBar) {
            $progressBar->finish();
        }
    }

    /**
     * Upload the given file to the given URL.
     */
    public function uploadFile(string $filePath, string $url, array $headers = [], ?ProgressBar $progressBar = null)
    {
        if (!is_readable($filePath)) {
            throw new RuntimeException(sprintf('Cannot read the "%s" file', $filePath));
        }

        $progressCallback = null;
        $file = fopen($filePath, 'r+');

        if (!is_resource($file)) {
            throw new RuntimeException(sprintf('Cannot open the "%s" file', $filePath));
        }

        if ($progressBar instanceof ProgressBar) {
            $progressBar->start((int) round(filesize($filePath) / 1024));

            $progressCallback = function ($_, $__, $___, $uploaded) use ($progressBar) {
                $progressBar->setProgress((int) round($uploaded / 1024));
            };
        }

        $this->client->request('PUT', $url, array_filter([
            'body' => $file,
            'headers' => empty($headers) ? null : $headers,
            'progress' => $progressCallback,
        ]));

        if ($progressBar instanceof ProgressBar) {
            $progressBar->finish();
        }

        if (is_resource($file)) {
            fclose($file);
        }
    }
}
