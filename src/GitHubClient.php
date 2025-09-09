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
use Illuminate\Support\Collection;
use Ymir\Cli\Exception\SystemException;

class GitHubClient
{
    /**
     * The HTTP client used to interact with the GitHub API.
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
     * Download the latest version of a repository from GitHub and return the Zip archive.
     */
    public function downloadLatestVersion(string $repository): \ZipArchive
    {
        $latestTag = $this->getTags($repository)->first();

        if (empty($latestTag['zipball_url'])) {
            throw new SystemException('Unable to parse the WordPress plugin versions from the GitHub API');
        }

        $downloadedZipFile = tmpfile();

        if (!is_resource($downloadedZipFile)) {
            throw new SystemException('Unable to open a temporary file');
        }

        fwrite($downloadedZipFile, (string) $this->client->request('GET', $latestTag['zipball_url'])->getBody());

        $downloadedZipArchive = new \ZipArchive();

        if (true !== $downloadedZipArchive->open(stream_get_meta_data($downloadedZipFile)['uri'])) {
            throw new SystemException(sprintf('Unable to open the "%s" repository Zip archive from GitHub', $repository));
        }

        return $downloadedZipArchive;
    }

    /**
     * Get the tags for the given repository.
     */
    public function getTags(string $repository): Collection
    {
        $response = $this->client->request('GET', sprintf('https://api.github.com/repos/%s/tags', $repository));

        if (200 !== $response->getStatusCode()) {
            throw new SystemException(sprintf('Unable to get the tags for the "%s" repository from the GitHub API', $repository));
        }

        $tags = json_decode((string) $response->getBody(), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new SystemException(sprintf('Failed to decode response from the GitHub API: %s', json_last_error_msg()));
        }

        return collect($tags);
    }
}
