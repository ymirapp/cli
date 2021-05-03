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
     * Add a bastion host to the given network.
     */
    public function addBastionHost(int $networkId): Collection
    {
        return $this->request('post', "/networks/{$networkId}/bastion-host");
    }

    /**
     * Add a NAT gateway to the given network.
     */
    public function addNatGateway(int $networkId)
    {
        $this->request('post', "/networks/{$networkId}/nat");
    }

    /**
     * Send signal to the Ymir API to cancel the deployment.
     */
    public function cancelDeployment(int $deploymentId)
    {
        $this->request('post', "/deployments/{$deploymentId}/cancel");
    }

    /**
     * Change the value of the DNS record in the given DNS zone ID or name.
     */
    public function changeDnsRecord($zoneIdOrName, string $type, string $name, string $value)
    {
        $zone = $this->getDnsZone($zoneIdOrName);

        $this->request('put', "/zones/{$zone['id']}/records", [
            'type' => $type,
            'name' => $name,
            'value' => $value,
        ]);
    }

    /**
     * Change the environment variables of the given project environment.
     */
    public function changeEnvironmentVariables(int $projectId, string $environment, array $variables, bool $overwrite = false): Collection
    {
        return $this->request('put', "/projects/{$projectId}/environments/{$environment}/variables", [
            'variables' => $variables,
            'overwrite' => $overwrite,
        ]);
    }

    /**
     * Change the value of the given secret for the given project environment.
     */
    public function changeSecret(int $projectId, string $environment, string $name, string $value): Collection
    {
        return $this->request('put', "/projects/{$projectId}/environments/{$environment}/secrets", [
            'name' => $name,
            'value' => $value,
        ]);
    }

    /**
     * Create a new SSL certificate.
     */
    public function createCertificate(int $providerId, string $domain, string $region): Collection
    {
        return $this->request('post', "/providers/{$providerId}/certificates", [
            'domain' => $domain,
            'region' => $region,
        ]);
    }

    /**
     * Create a new database on the given database server.
     */
    public function createDatabase(int $databaseId, string $name): Collection
    {
        return $this->request('post', "/database-servers/{$databaseId}/databases", [
            'name' => $name,
        ]);
    }

    /**
     * Create a new database on the given network.
     */
    public function createDatabaseServer(string $name, int $networkId, string $type, int $storage = 100, bool $public = false): Collection
    {
        return $this->request('post', "/networks/{$networkId}/database-servers", [
            'name' => $name,
            'publicly_accessible' => $public,
            'storage' => $storage,
            'type' => $type,
        ]);
    }

    /**
     * Create a new user on the given database server.
     */
    public function createDatabaseUser(int $databaseId, string $username, array $databases = []): Collection
    {
        return $this->request('post', "/database-servers/{$databaseId}/users", [
            'databases' => $databases,
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
    public function createDnsZone(int $providerId, string $name): Collection
    {
        return $this->request('post', "/providers/{$providerId}/zones", [
            'domain_name' => $name,
        ]);
    }

    /**
     * Create a new email identity on the given cloud provider.
     */
    public function createEmailIdentity(int $providerId, string $name, string $region): Collection
    {
        $type = filter_var($name, FILTER_VALIDATE_EMAIL) ? 'email' : 'domain';

        return $this->request('post', "/providers/{$providerId}/email-identities", [
            $type => $name,
            'region' => $region,
        ]);
    }

    /**
     * Create a new environment with the given name for the given project.
     */
    public function createEnvironment(int $projectId, string $name): Collection
    {
        return $this->request('post', "/projects/{$projectId}/environments", [
            'name' => $name,
        ]);
    }

    /**
     * Create a new function invocation for the given project on the given environment.
     */
    public function createInvocation(int $projectId, string $environment, array $payload): Collection
    {
        return $this->request('post', "/projects/{$projectId}/environments/{$environment}/invocations", [
            'payload' => $payload,
        ]);
    }

    /**
     * Create a new network.
     */
    public function createNetwork(int $providerId, string $name, string $region): Collection
    {
        return $this->request('post', "/providers/{$providerId}/networks", [
            'name' => $name,
            'region' => $region,
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
    public function createProvider(string $name, array $credentials, int $teamId): Collection
    {
        return $this->request('post', "/teams/{$teamId}/providers", [
            'name' => $name,
            'credentials' => $credentials,
        ]);
    }

    /**
     * Create a new deployment redeploying the given project on the given environment.
     */
    public function createRedeployment(int $projectId, string $environment): Collection
    {
        return $this->request('post', "/projects/{$projectId}/environments/{$environment}/redeployments");
    }

    /**
     * Create a new deployment to rollback to the given deployment.
     */
    public function createRollback(int $projectId, string $environment, int $deploymentId): Collection
    {
        return $this->request('post', "/projects/{$projectId}/environments/{$environment}/rollbacks", [
            'deployment' => $deploymentId,
        ]);
    }

    /**
     * Create a new team with the given name.
     */
    public function createTeam(string $name): Collection
    {
        return $this->request('post', '/teams', [
            'name' => $name,
        ]);
    }

    /**
     * Delete the given SSL certificate.
     */
    public function deleteCertificate(int $certificateId)
    {
        $this->request('delete', "/certificates/{$certificateId}");
    }

    /**
     * Delete a the given database on the given database server.
     */
    public function deleteDatabase(int $databaseId, string $name): Collection
    {
        return $this->request('delete', "/database-servers/{$databaseId}/databases/{$name}");
    }

    /**
     * Delete the given database server.
     */
    public function deleteDatabaseServer(int $databaseId)
    {
        $this->request('delete', "/database-servers/{$databaseId}");
    }

    /**
     * Delete a the given database user on the given database server.
     */
    public function deleteDatabaseUser(int $databaseId, int $userId): Collection
    {
        return $this->request('delete', "/database-servers/{$databaseId}/users/{$userId}");
    }

    /**
     * Delete the given DNS record.
     */
    public function deleteDnsRecord(int $zoneId, int $recordId)
    {
        $this->request('delete', "/zones/{$zoneId}/records/{$recordId}");
    }

    /**
     * Delete the given DNS zone.
     */
    public function deleteDnsZone(int $zoneId)
    {
        $this->request('delete', "/zones/{$zoneId}");
    }

    /**
     * Delete the given email identity.
     */
    public function deleteEmailIdentity(int $identityId)
    {
        $this->request('delete', "/email-identities/{$identityId}");
    }

    /**
     * Delete the given environment on the given project.
     */
    public function deleteEnvironment(int $projectId, string $environment, bool $deleteResources = false)
    {
        $this->request('delete', "/projects/{$projectId}/environments/{$environment}", [
            'delete_resources' => $deleteResources,
        ]);
    }

    /**
     * Delete the given network.
     */
    public function deleteNetwork(int $networkId)
    {
        $this->request('delete', "/networks/{$networkId}");
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
     * Delete the given cloud provider.
     */
    public function deleteProvider(int $providerId)
    {
        $this->request('delete', "/providers/{$providerId}");
    }

    /**
     * Delete the given secret.
     */
    public function deleteSecret($secretId)
    {
        $this->request('delete', "/secrets/{$secretId}");
    }

    /**
     * Get an access token for the given email and password.
     */
    public function getAccessToken(string $email, string $password, ?string $authenticationCode = null): string
    {
        $response = $this->request('post', '/token', [
            'host' => gethostname(),
            'email' => $email,
            'password' => $password,
            'authentication_code' => $authenticationCode,
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
     * Get the bastion host with the given ID.
     */
    public function getBastionHost(int $bastionHostId): Collection
    {
        return $this->request('get', "/bastion-hosts/{$bastionHostId}");
    }

    /**
     * Get the SSL certificates with the given ID.
     */
    public function getCertificate(int $certificateId): Collection
    {
        return $this->request('get', "/certificates/{$certificateId}");
    }

    /**
     * Get the SSL certificates that belong to the given team.
     */
    public function getCertificates(int $teamId): Collection
    {
        return $this->request('get', "/teams/{$teamId}/certificates");
    }

    /**
     * Get the list of databases on the given database server.
     */
    public function getDatabases(int $databaseId): Collection
    {
        return $this->request('get', "/database-servers/{$databaseId}/databases");
    }

    /**
     * Get the information on the database server with the given database ID or name.
     */
    public function getDatabaseServer(int $databaseId): Collection
    {
        return $this->request('get', "/database-servers/{$databaseId}");
    }

    /**
     * Get the database servers that belong to the given team.
     */
    public function getDatabaseServers(int $teamId): Collection
    {
        return $this->request('get', "/teams/{$teamId}/database-servers");
    }

    /**
     * Get the types of database server available on the given cloud provider.
     */
    public function getDatabaseServerTypes(int $providerId): Collection
    {
        $types = $this->request('get', "/providers/{$providerId}/database-servers/types");

        if ($types->isEmpty()) {
            throw new RuntimeException('The Ymir API failed to return information on the database instance types');
        }

        return $types;
    }

    /**
     * Get the list of database users on the given database server.
     */
    public function getDatabaseUsers(int $databaseId): Collection
    {
        return $this->request('get', "/database-servers/{$databaseId}/users");
    }

    /**
     * Get the details on the given deployment.
     */
    public function getDeployment(int $deploymentId): Collection
    {
        return $this->request('get', "/deployments/{$deploymentId}");
    }

    /**
     * Get all the deployments for the given project on the given environment.
     */
    public function getDeployments(int $projectId, string $environment): Collection
    {
        return $this->request('get', "/projects/{$projectId}/environments/{$environment}/deployments");
    }

    /**
     * Get the DNS records belonging to the given DNS zone.
     */
    public function getDnsRecords($zoneIdOrName): Collection
    {
        $zone = $this->getDnsZone($zoneIdOrName);

        return $this->request('get', "/zones/{$zone['id']}/records");
    }

    /**
     * Get the DNS zone information from the given zone ID or name.
     */
    public function getDnsZone($idOrName): array
    {
        $zone = null;

        if (is_numeric($idOrName)) {
            $zone = $this->request('get', "/zones/{$idOrName}")->toArray();
        } elseif (is_string($idOrName)) {
            $zone = $this->getDnsZones($this->cliConfiguration->getActiveTeamId())->firstWhere('domain_name', $idOrName);
        }

        if (!is_array($zone) || !isset($zone['id']) || !is_numeric($zone['id'])) {
            throw new RuntimeException(sprintf('Unable to find a DNS zone with "%s" as the ID or name', $idOrName));
        }

        return $zone;
    }

    /**
     * Get the DNS zones that belong to the given team.
     */
    public function getDnsZones(int $teamId): Collection
    {
        return $this->request('get', "/teams/{$teamId}/zones");
    }

    /**
     * Get the email identities that belong to the given team.
     */
    public function getEmailIdentities(int $teamId): Collection
    {
        return $this->request('get', "/teams/{$teamId}/email-identities");
    }

    /**
     * Get the email identity information from the given zone ID or name.
     */
    public function getEmailIdentity($idOrName): array
    {
        $identity = null;

        if (is_numeric($idOrName)) {
            $identity = $this->request('get', "/email-identities/$idOrName")->toArray();
        } elseif (is_string($idOrName)) {
            $identity = $this->getEmailIdentities($this->cliConfiguration->getActiveTeamId())->firstWhere('name', $idOrName);
        }

        if (!is_array($identity) || !isset($identity['id']) || !is_numeric($identity['id'])) {
            throw new RuntimeException(sprintf('Unable to find a email identity with "%s" as the ID or name', $idOrName));
        }

        return $identity;
    }

    /**
     * Get the project environment details.
     */
    public function getEnvironment(int $projectId, string $environment): Collection
    {
        return $this->request('get', "/projects/{$projectId}/environments/$environment");
    }

    /**
     * Get the details on the project's environments.
     */
    public function getEnvironments(int $projectId): Collection
    {
        return $this->request('get', "/projects/{$projectId}/environments");
    }

    /**
     * Get the project environment's vanity domain name.
     */
    public function getEnvironmentVanityDomainName(int $projectId, string $environment): string
    {
        $environment = $this->getEnvironment($projectId, $environment);

        if (!$environment->has('vanity_domain_name')) {
            throw new RuntimeException('Unable to get the environment vanity domain name');
        }

        return (string) $environment->get('vanity_domain_name');
    }

    /**
     * Get the environment variables of the given project environment.
     */
    public function getEnvironmentVariables(int $projectId, string $environment): Collection
    {
        return $this->request('get', "/projects/{$projectId}/environments/{$environment}/variables");
    }

    /**
     * Get the function invocation with the given ID.
     */
    public function getInvocation(int $invocationId): Collection
    {
        return $this->request('get', "/invocations/{$invocationId}");
    }

    /**
     * Get the network.
     */
    public function getNetwork(int $networkId): Collection
    {
        return $this->request('get', "/networks/{$networkId}");
    }

    /**
     * Get the project.
     */
    public function getProject(int $projectId): Collection
    {
        return $this->request('get', "/projects/{$projectId}");
    }

    /**
     * Get the cloud provider with the given ID.
     */
    public function getProvider(int $providerId): Collection
    {
        return $this->request('get', "/providers/{$providerId}");
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
     * Get all the secrets for the given project on the given environment.
     */
    public function getSecrets(int $projectId, string $environment): Collection
    {
        return $this->request('get', "/projects/{$projectId}/environments/{$environment}/secrets");
    }

    /**
     * Get the signed asset requests for the given asset files.
     */
    public function getSignedAssetRequests(int $deploymentId, array $assets): Collection
    {
        $requests = $this->request('post', "/deployments/{$deploymentId}/signed-assets", ['assets' => $assets]);

        if (!empty($assets) && empty($requests)) {
            throw new RuntimeException('Unable to get authorized asset requests from the Ymir API');
        }

        return $requests;
    }

    /**
     * Get the signed asset requests for the given asset files.
     */
    public function getSignedUploadRequests(int $projectId, string $environment, array $uploads): Collection
    {
        $requests = $this->request('post', "/projects/{$projectId}/environments/{$environment}/signed-uploads", ['uploads' => $uploads]);

        if (!empty($uploads) && empty($requests)) {
            throw new RuntimeException('Unable to get authorized uploads requests from the Ymir API');
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
     * Get the database servers that the team has access to.
     */
    public function getTeamDatabaseServers($teamId): Collection
    {
        return $this->request('get', "/teams/{$teamId}/database-servers");
    }

    /**
     * Get the networks that belong to the given team.
     */
    public function getTeamNetworks(int $teamId): Collection
    {
        return $this->request('get', "/teams/{$teamId}/networks");
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
     * Change the value of the DNS record in the given DNS zone ID or name.
     */
    public function importDnsRecord($zoneIdOrName, array $subdomains = [])
    {
        $zone = $this->getDnsZone($zoneIdOrName);

        $this->request('post', "/zones/{$zone['id']}/import-records", [
            'subdomains' => array_filter($subdomains),
        ]);
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
     * Remove the bastion host from the given network.
     */
    public function removeBastionHost(int $networkId)
    {
        $this->request('delete', "/networks/{$networkId}/bastion-host");
    }

    /**
     * Remove the NAT gateway from the given network.
     */
    public function removeNatGateway(int $networkId)
    {
        $this->request('delete', "/networks/{$networkId}/nat");
    }

    /**
     * Send signal to the Ymir API to start the deployment.
     */
    public function startDeployment(int $deploymentId)
    {
        $this->request('post', "/deployments/{$deploymentId}/start");
    }

    /**
     * Update the give database server.
     */
    public function updateDatabaseServer(int $databaseId, int $storage, string $type)
    {
        $this->request('put', "/database-servers/{$databaseId}", [
            'storage' => $storage,
            'type' => $type,
        ]);
    }

    /**
     * Update the given cloud provider.
     */
    public function updateProvider(int $providerId, array $credentials, string $name)
    {
        $this->request('put', "/providers/{$providerId}", [
            'name' => $name,
            'credentials' => $credentials,
        ]);
    }

    /**
     * Validates the project configuration. Returns nothing if no errors were found.
     */
    public function validateProjectConfiguration(ProjectConfiguration $projectConfiguration, array $environments = [])
    {
        $this->request('post', "/projects/{$projectConfiguration->getProjectId()}/validate-configuration", [
            'configuration' => $projectConfiguration->toArray(),
            'environments' => $environments,
        ]);
    }

    /**
     * Send a request to the Ymir API.
     */
    private function request(string $method, string $uri, array $body = []): Collection
    {
        $method = strtolower($method);
        $options = [
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'verify' => false,
        ];
        $uri = ltrim($uri, '/');

        if ($this->cliConfiguration->hasAccessToken()) {
            $options['headers']['Authorization'] = 'Bearer '.$this->cliConfiguration->getAccessToken();
        }

        if (in_array($method, ['delete', 'post', 'put'])) {
            $options['json'] = $body;
        }

        try {
            $response = $this->client->request($method, $uri, $options);
        } catch (ClientException $exception) {
            throw new ApiClientException($exception);
        }

        return collect(json_decode((string) $response->getBody(), true));
    }
}
