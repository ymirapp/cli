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
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Console\Exception\RuntimeException;
use Tightenco\Collect\Support\Collection;
use Ymir\Cli\Exception\ApiClientException;

class ApiClient
{
    /**
     * The base URL used to interact with the Ymir API.
     *
     * @var string
     */
    private $baseUrl;

    /**
     * The global Ymir CLI configuration.
     *
     * @var CliConfiguration
     */
    private $cliConfiguration;

    /**
     * The HTTP client used to interact with the Ymir API.
     *
     * @var ClientInterface
     */
    private $client;

    /**
     * Constructor.
     */
    public function __construct(string $baseUrl, ClientInterface $client, CliConfiguration $cliConfiguration)
    {
        $this->baseUrl = rtrim($baseUrl, '/').'/';
        $this->client = $client;
        $this->cliConfiguration = $cliConfiguration;
    }

    /**
     * Send signal to the Ymir API to cancel the deployment.
     */
    public function cancelDeployment(int $deploymentId)
    {
        $this->request('post', "/deployments/{$deploymentId}/cancel");
    }

    /**
     * Create a new database on the given network.
     */
    public function createDatabase(string $name, int $networkId, string $type, int $storage = 100, bool $public = false): Collection
    {
        return $this->request('post', "/networks/{$networkId}/databases", [
            'name' => $name,
            'publicly_accessible' => $public,
            'storage' => $storage,
            'type' => $type,
        ]);
    }

    /**
     * Create a new user on the given database.
     */
    public function createDatabaseUser(int $databaseId, string $username): Collection
    {
        return $this->request('post', "/databases/{$databaseId}/users", [
            'username' => $username,
        ]);
    }

    /**
     * Create a new deployment for the given project on the given environment.
     */
    public function createDeployment(int $projectId, string $environment, ProjectConfiguration $projectConfiguration): Collection
    {
        return $this->request('post', "/projects/{$projectId}/environments/{$environment}/deployments", [
            'configuration' => $projectConfiguration->toArray(),
        ]);
    }

    /**
     * Create a new DNS zone on the given cloud provider.
     */
    public function createDnsZone(int $providerId, string $name)
    {
        return $this->request('post', "/providers/{$providerId}/zones", [
            'name' => $name,
        ]);
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
     * Delete the given database.
     */
    public function deleteDatabase(int $databaseId)
    {
        $this->request('delete', "/databases/{$databaseId}");
    }

    /**
     * Delete the given project.
     */
    public function deleteProject(int $projectId, bool $deleteResources = false)
    {
        $this->request('delete', "/projects/{$projectId}", [
            'delete_resources' => $deleteResources,
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
            throw new RuntimeException('The Ymir API didn\'t return an access token');
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
     * Get the upload URL for the artifact file.
     */
    public function getArtifactUploadUrl(int $deploymentId): string
    {
        $uploadUrl = (string) $this->request('get', "/deployments/{$deploymentId}/artifact")->get('upload_url');

        if (empty($uploadUrl)) {
            throw new RuntimeException('Unable to get an artifact upload URL from the Ymir API');
        }

        return $uploadUrl;
    }

    /**
     * Get the databases that belong to the given team.
     */
    public function getDatabases(int $teamId): Collection
    {
        return $this->request('get', "/teams/{$teamId}/databases");
    }

    /**
     * Get the database types available on the given cloud provider.
     */
    public function getDatabaseTypes(int $providerId): Collection
    {
        return $this->request('get', "/providers/{$providerId}/databases/types");
    }

    /**
     * Get the details on the given deployment.
     */
    public function getDeployment(int $deploymentId): Collection
    {
        return $this->request('get', "/deployments/{$deploymentId}");
    }

    /**
     * Get the DNS zone details.
     */
    public function getDnsZone(int $zoneId): Collection
    {
        return $this->request('get', "/zones/{$zoneId}");
    }

    /**
     * Get the DNS zones that belong to the given team.
     */
    public function getDnsZones(int $teamId): Collection
    {
        return $this->request('get', "/teams/{$teamId}/zones");
    }

    /**
     * Get the project environment details.
     */
    public function getEnvironment(int $projectId, string $environment): Collection
    {
        return $this->request('get', "/projects/{$projectId}/environments/$environment");
    }

    /**
     * Get the networks that belong to the given team.
     */
    public function getNetworks(int $teamId): Collection
    {
        return $this->request('get', "/teams/{$teamId}/networks");
    }

    /**
     * Get the project.
     */
    public function getProject(int $projectId): Collection
    {
        return $this->request('get', "/projects/{$projectId}");
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
     * Get the signed asset requests for the given asset files.
     */
    public function getSignedAssetRequests(int $deploymentId, array $assets): Collection
    {
        $requests = $this->request('get', "/deployments/{$deploymentId}/signed-assets", ['assets' => $assets]);

        if (!empty($assets) && empty($requests)) {
            throw new RuntimeException('Unable to get authorized asset requests from the Ymir API');
        }

        return $requests;
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
     * Invalidate the content delivery network cache for the given project environment.
     */
    public function invalidateCache(int $projectId, string $environment, array $paths)
    {
        $this->request('post', "/projects/{$projectId}/environments/$environment/invalidate-cache", [
            'paths' => $paths,
        ]);
    }

    /**
     * Checks if the client is authenticated with the Ymir API.
     */
    public function isAuthenticated(): bool
    {
        try {
            return $this->cliConfiguration->hasAccessToken() && !empty($this->getUser());
        } catch (ApiClientException $exception) {
            if (401 === $exception->getCode()) {
                return false;
            }

            throw $exception;
        }
    }

    /**
     * Send signal to the Ymir API to start the deployment.
     */
    public function startDeployment(int $deploymentId)
    {
        $this->request('post', "/deployments/{$deploymentId}/start");
    }

    /**
     * Validates the project configuration. Returns nothing if no errors were found.
     */
    public function validateProjectConfiguration(ProjectConfiguration $projectConfiguration, array $environments = [])
    {
        $this->request('get', "/projects/{$projectConfiguration->getProjectId()}/validate-configuration", [
            'configuration' => $projectConfiguration->toArray(),
            'environments' => $environments,
        ]);
    }

    /**
     * Send a request to the Ymir API.
     */
    private function request(string $method, string $uri, array $body = []): Collection
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
        $uri = ltrim($uri, '/');

        if ($this->cliConfiguration->hasAccessToken()) {
            $headers['Authorization'] = 'Bearer '.$this->cliConfiguration->getAccessToken();
        }

        try {
            $response = $this->client->request($method, $uri, [
                'base_uri' => $this->baseUrl,
                'headers' => $headers,
                'json' => $body,
                'verify' => false,
            ]);
        } catch (ClientException $exception) {
            throw new ApiClientException($exception);
        }

        return collect(json_decode((string) $response->getBody(), true));
    }
}
