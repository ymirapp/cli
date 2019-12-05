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
use GuzzleHttp\Exception\ClientException;
use Placeholder\Cli\Exception\ApiClientException;
use Symfony\Component\Console\Exception\RuntimeException;
use Tightenco\Collect\Support\Collection;

class ApiClient
{
    /**
     * The base URL used to interact with the placeholder API.
     *
     * @var string
     */
    private $baseUrl;

    /**
     * The HTTP client used to interact with the placeholder API.
     *
     * @var ClientInterface
     */
    private $client;

    /**
     * The global placeholder CLI configuration.
     *
     * @var Configuration
     */
    private $configuration;

    /**
     * Constructor.
     */
    public function __construct(string $baseUrl, ClientInterface $client, Configuration $configuration)
    {
        $this->baseUrl = rtrim($baseUrl, '/').'/';
        $this->client = $client;
        $this->configuration = $configuration;
    }

    /**
     * Create a new project with the given cloud provider.
     */
    public function createProject(int $providerId, string $name, string $region): Collection
    {
        return $this->request('post', "/providers/{$providerId}/projects", [
            'name' => $name,
            'region' => $region,
        ]);
    }

    /**
     * Create a new cloud provider with the given name and credentials.
     */
    public function createProvider(string $name, array $credentials, int $teamId)
    {
        $this->request('post', "/teams/{$teamId}/providers", [
            'provider' => 'aws',
            'name' => $name,
            'credentials' => $credentials,
        ]);
    }

    /**
     * Create a new team with the given name.
     */
    public function createTeam(string $name)
    {
        $this->request('post', '/teams', [
            'name' => $name,
        ]);
    }

    /**
     * Get an access token for the given email and password.
     */
    public function getAccessToken(string $email, string $password): string
    {
        $response = $this->request('post', '/token', [
            'host' => gethostname(),
            'email' => $email,
            'password' => $password,
        ]);

        if (empty($response['access_token'])) {
            throw new RuntimeException('The placeholder API didn\'t return an access token');
        }

        return $response['access_token'];
    }

    /**
     * Get the user's currently active team.
     */
    public function getActiveTeam(): Collection
    {
        return $this->request('get', '/teams/active');
    }

    /**
     * Get the cloud providers for the given team ID.
     */
    public function getProviders(int $teamId): Collection
    {
        return $this->request('get', "/teams/{$teamId}/providers");
    }

    /**
     * Get the regions supported by the given cloud provider.
     */
    public function getRegions(int $providerId): Collection
    {
        return $this->request('get', "/providers/{$providerId}/regions");
    }

    /**
     * Get the details on the given team.
     */
    public function getTeam($teamId): Collection
    {
        return $this->request('get', '/teams/'.$teamId);
    }

    /**
     * Get the teams the user is a member of.
     */
    public function getTeams(): Collection
    {
        return $this->request('get', '/teams');
    }

    /**
     * Get the authenticated user.
     */
    public function getUser(): Collection
    {
        return $this->request('get', '/user');
    }

    /**
     * Checks if the client is authenticated with the placeholder API.
     */
    public function isAuthenticated(): bool
    {
        try {
            return $this->configuration->has('token') && !empty($this->getUser());
        } catch (ApiClientException $exception) {
            if (401 === $exception->getCode()) {
                return false;
            }

            throw $exception;
        }
    }

    /**
     * Send a request to the placeholder API.
     */
    private function request(string $method, string $uri, array $body = []): Collection
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
        $uri = ltrim($uri, '/');

        if ($this->configuration->has('token')) {
            $headers['Authorization'] = 'Bearer '.$this->configuration->get('token');
        }

        try {
            $response = $this->client->request($method, $uri, [
                'base_uri' => $this->baseUrl,
                'headers' => $headers,
                'json' => $body,
            ]);
        } catch (ClientException $exception) {
            throw new ApiClientException($exception);
        }

        return new Collection(json_decode((string) $response->getBody(), true));
    }
}
