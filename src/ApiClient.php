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

use Illuminate\Support\Collection;
use Symfony\Component\Console\Exception\RuntimeException;
use Ymir\Cli\ProjectConfiguration\ProjectConfiguration;
use Ymir\Sdk\Client;
use Ymir\Sdk\Exception\ClientException;
use Ymir\Sdk\Exception\UnexpectedApiResponseException;

class ApiClient
{
    /**
     * The global Ymir CLI configuration.
     *
     * @var CliConfiguration
     */
    private $cliConfiguration;

    /**
     * The Ymir API client.
     *
     * @var Client
     */
    private $client;

    /**
     * Constructor.
     */
    public function __construct(Client $client, CliConfiguration $cliConfiguration)
    {
        $this->client = $client;
        $this->cliConfiguration = $cliConfiguration;

        if ($this->cliConfiguration->hasAccessToken()) {
            $this->client->setAccessToken($this->cliConfiguration->getAccessToken());
        }
    }

    /**
     * Add a bastion host to the given network.
     */
    public function addBastionHost(int $networkId): Collection
    {
        return $this->client->addBastionHost($networkId);
    }

    /**
     * Add a NAT gateway to the given network.
     */
    public function addNatGateway(int $networkId)
    {
        $this->client->addNatGateway($networkId);
    }

    /**
     * Send signal to the Ymir API to cancel the deployment.
     */
    public function cancelDeployment(int $deploymentId)
    {
        $this->client->cancelDeployment($deploymentId);
    }

    /**
     * Change the lock of the given database server.
     */
    public function changeDatabaseServerLock(int $databaseServerId, bool $locked)
    {
        $this->client->changeDatabaseServerLock($databaseServerId, $locked);
    }

    /**
     * Change the value of the DNS record in the given DNS zone ID or name.
     */
    public function changeDnsRecord($zoneIdOrName, string $type, string $name, string $value)
    {
        $zone = $this->getDnsZone($zoneIdOrName);

        $this->client->changeDnsRecord($zone['id'], $type, $name, $value);
    }

    /**
     * Change the environment variables of the given project environment.
     */
    public function changeEnvironmentVariables(int $projectId, string $environment, array $variables, bool $overwrite = false)
    {
        $this->client->changeEnvironmentVariables($projectId, $environment, $variables, $overwrite);
    }

    /**
     * Change the value of the given secret for the given project environment.
     */
    public function changeSecret(int $projectId, string $environment, string $name, string $value)
    {
        $this->client->changeSecret($projectId, $environment, $name, $value);
    }

    /**
     * Create a new cache on the given network.
     */
    public function createCache(int $networkId, string $name, string $type): Collection
    {
        return $this->client->createCache($networkId, $name, $type);
    }

    /**
     * Create a new SSL certificate.
     */
    public function createCertificate(int $providerId, array $domains, string $region): Collection
    {
        return $this->client->createCertificate($providerId, $domains, $region);
    }

    /**
     * Create a new database on the given database server.
     */
    public function createDatabase(int $databaseServerId, string $name): Collection
    {
        return $this->client->createDatabase($databaseServerId, $name);
    }

    /**
     * Create a new database on the given network.
     */
    public function createDatabaseServer(int $networkId, string $name, string $type, ?int $storage = 50, bool $public = false): Collection
    {
        return $this->client->createDatabaseServer($networkId, $name, $type, $storage, $public);
    }

    /**
     * Create a new user on the given database server.
     */
    public function createDatabaseUser(int $databaseServerId, string $username, array $databases = []): Collection
    {
        return $this->client->createDatabaseUser($databaseServerId, $username, $databases);
    }

    /**
     * Create a new deployment for the given project on the given environment.
     */
    public function createDeployment(int $projectId, string $environment, ProjectConfiguration $projectConfiguration, string $assetsHash = null): Collection
    {
        return $this->client->createDeployment($projectId, $environment, $projectConfiguration->toArray(), $assetsHash);
    }

    /**
     * Create a new DNS zone on the given cloud provider.
     */
    public function createDnsZone(int $providerId, string $name): Collection
    {
        return $this->client->createDnsZone($providerId, $name);
    }

    /**
     * Create a new email identity on the given cloud provider.
     */
    public function createEmailIdentity(int $providerId, string $name, string $region): Collection
    {
        return $this->client->createEmailIdentity($providerId, $name, $region);
    }

    /**
     * Create a new environment with the given name for the given project.
     */
    public function createEnvironment(int $projectId, string $name): Collection
    {
        return $this->client->createEnvironment($projectId, $name);
    }

    /**
     * Create a new function invocation for the given project on the given environment.
     */
    public function createInvocation(int $projectId, string $environment, array $payload): Collection
    {
        return $this->client->createInvocation($projectId, $environment, $payload);
    }

    /**
     * Create a new network.
     */
    public function createNetwork(int $providerId, string $name, string $region): Collection
    {
        return $this->client->createNetwork($providerId, $name, $region);
    }

    /**
     * Create a new project with the given cloud provider.
     */
    public function createProject(int $providerId, string $name, string $region, array $environments = []): Collection
    {
        return $this->client->createProject($providerId, $name, $region, $environments);
    }

    /**
     * Create a new cloud provider with the given name and credentials.
     */
    public function createProvider(int $teamId, string $name, array $credentials): Collection
    {
        return $this->client->createProvider($teamId, $name, $credentials);
    }

    /**
     * Create a new deployment redeploying the given project on the given environment.
     */
    public function createRedeployment(int $projectId, string $environment): Collection
    {
        return $this->client->createRedeployment($projectId, $environment);
    }

    /**
     * Create a new deployment to rollback to the given deployment.
     */
    public function createRollback(int $projectId, string $environment, int $deploymentId): Collection
    {
        return $this->client->createRollback($projectId, $environment, $deploymentId);
    }

    /**
     * Create a new team with the given name.
     */
    public function createTeam(string $name): Collection
    {
        return $this->client->createTeam($name);
    }

    /**
     * Delete the given cache.
     */
    public function deleteCache(int $cacheId)
    {
        $this->client->deleteCache($cacheId);
    }

    /**
     * Delete the given SSL certificate.
     */
    public function deleteCertificate(int $certificateId)
    {
        $this->client->deleteCertificate($certificateId);
    }

    /**
     * Delete the given database on the given database server.
     */
    public function deleteDatabase(int $databaseServerId, string $name)
    {
        $this->client->deleteDatabase($databaseServerId, $name);
    }

    /**
     * Delete the given database server.
     */
    public function deleteDatabaseServer(int $databaseServerId)
    {
        $this->client->deleteDatabaseServer($databaseServerId);
    }

    /**
     * Delete the given database user on the given database server.
     */
    public function deleteDatabaseUser(int $databaseServerId, int $userId)
    {
        $this->client->deleteDatabaseUser($databaseServerId, $userId);
    }

    /**
     * Delete the given DNS record.
     */
    public function deleteDnsRecord(int $zoneId, int $recordId)
    {
        $this->client->deleteDnsRecord($zoneId, $recordId);
    }

    /**
     * Delete the given DNS zone.
     */
    public function deleteDnsZone(int $zoneId)
    {
        $this->client->deleteDnsZone($zoneId);
    }

    /**
     * Delete the given email identity.
     */
    public function deleteEmailIdentity(int $identityId)
    {
        $this->client->deleteEmailIdentity($identityId);
    }

    /**
     * Delete the given environment on the given project.
     */
    public function deleteEnvironment(int $projectId, string $environment, bool $deleteResources = false)
    {
        $this->client->deleteEnvironment($projectId, $environment, $deleteResources);
    }

    /**
     * Delete the given network.
     */
    public function deleteNetwork(int $networkId)
    {
        $this->client->deleteNetwork($networkId);
    }

    /**
     * Delete the given project.
     */
    public function deleteProject(int $projectId, bool $deleteResources = false)
    {
        $this->client->deleteProject($projectId, $deleteResources);
    }

    /**
     * Delete the given cloud provider.
     */
    public function deleteProvider(int $providerId)
    {
        $this->client->deleteProvider($providerId);
    }

    /**
     * Delete the given secret.
     */
    public function deleteSecret($secretId)
    {
        $this->client->deleteSecret($secretId);
    }

    /**
     * Get an access token for the given email and password.
     */
    public function getAccessToken(string $email, string $password, string $authenticationCode = null): string
    {
        try {
            return $this->client->getAccessToken($email, $password, $authenticationCode);
        } catch (UnexpectedApiResponseException $exception) {
            throw new RuntimeException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Get the user's currently active team.
     */
    public function getActiveTeam(): Collection
    {
        return $this->client->getActiveTeam();
    }

    /**
     * Get the upload URL for the artifact file.
     */
    public function getArtifactUploadUrl(int $deploymentId): string
    {
        try {
            return $this->client->getArtifactUploadUrl($deploymentId);
        } catch (UnexpectedApiResponseException $exception) {
            throw new RuntimeException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Get the authenticated user.
     */
    public function getAuthenticatedUser(): Collection
    {
        return $this->client->getAuthenticatedUser();
    }

    /**
     * Get the bastion host with the given ID.
     */
    public function getBastionHost(int $bastionHostId): Collection
    {
        return $this->client->getBastionHost($bastionHostId);
    }

    /**
     * Get the caches that belong to the given team.
     */
    public function getCaches(int $teamId): Collection
    {
        return $this->client->getCaches($teamId);
    }

    /**
     * Get the types of cache available on the given cloud provider.
     */
    public function getCacheTypes(int $providerId): Collection
    {
        try {
            return $this->client->getCacheTypes($providerId);
        } catch (UnexpectedApiResponseException $exception) {
            throw new RuntimeException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Get the SSL certificates with the given ID.
     */
    public function getCertificate(int $certificateId): Collection
    {
        return $this->client->getCertificate($certificateId);
    }

    /**
     * Get the SSL certificates that belong to the given team.
     */
    public function getCertificates(int $teamId): Collection
    {
        return $this->client->getCertificates($teamId);
    }

    /**
     * Get the list of databases on the given database server.
     */
    public function getDatabases(int $databaseServerId): Collection
    {
        return $this->client->getDatabases($databaseServerId);
    }

    /**
     * Get the information on the database server with the given database ID or name.
     */
    public function getDatabaseServer(int $databaseServerId): Collection
    {
        return $this->client->getDatabaseServer($databaseServerId);
    }

    /**
     * Get the database servers that belong to the given team.
     */
    public function getDatabaseServers(int $teamId): Collection
    {
        return $this->client->getDatabaseServers($teamId);
    }

    /**
     * Get the types of database server available on the given cloud provider.
     */
    public function getDatabaseServerTypes(int $providerId): Collection
    {
        try {
            return $this->client->getDatabaseServerTypes($providerId);
        } catch (UnexpectedApiResponseException $exception) {
            throw new RuntimeException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Get the list of database users on the given database server.
     */
    public function getDatabaseUsers(int $databaseServerId): Collection
    {
        return $this->client->getDatabaseUsers($databaseServerId);
    }

    /**
     * Get the details on the given deployment.
     */
    public function getDeployment(int $deploymentId): Collection
    {
        return $this->client->getDeployment($deploymentId);
    }

    /**
     * Get the container image used by the deployment.
     */
    public function getDeploymentImage(int $deploymentId): Collection
    {
        return $this->client->getDeploymentImage($deploymentId);
    }

    /**
     * Get all the deployments for the given project on the given environment.
     */
    public function getDeployments(int $projectId, string $environment): Collection
    {
        return $this->client->getDeployments($projectId, $environment);
    }

    /**
     * Get the DNS records belonging to the given DNS zone.
     */
    public function getDnsRecords($zoneIdOrName): Collection
    {
        $zone = $this->getDnsZone($zoneIdOrName);

        return $this->client->getDnsRecords($zone['id']);
    }

    /**
     * Get the DNS zone information from the given zone ID or name.
     */
    public function getDnsZone($idOrName): Collection
    {
        $zone = null;

        if (is_numeric($idOrName)) {
            $zone = $this->client->getDnsZone((int) $idOrName)->toArray();
        } elseif (is_string($idOrName)) {
            $zone = $this->getDnsZones($this->cliConfiguration->getActiveTeamId())->firstWhere('domain_name', $idOrName);
        }

        if (!is_array($zone) || !isset($zone['id']) || !is_numeric($zone['id'])) {
            throw new RuntimeException(sprintf('Unable to find a DNS zone with "%s" as the ID or name', $idOrName));
        }

        return collect($zone);
    }

    /**
     * Get the DNS zones that belong to the given team.
     */
    public function getDnsZones(int $teamId): Collection
    {
        return $this->client->getDnsZones($teamId);
    }

    /**
     * Get the email identities that belong to the given team.
     */
    public function getEmailIdentities(int $teamId): Collection
    {
        return $this->client->getEmailIdentities($teamId);
    }

    /**
     * Get the project environment details.
     */
    public function getEnvironment(int $projectId, string $environment): Collection
    {
        return $this->client->getEnvironment($projectId, $environment);
    }

    /**
     * Get the project environment's metrics.
     */
    public function getEnvironmentMetrics(int $projectId, string $environment, string $period): Collection
    {
        return $this->client->getEnvironmentMetrics($projectId, $environment, $period);
    }

    /**
     * Get the details on the project's environments.
     */
    public function getEnvironments(int $projectId): Collection
    {
        return $this->client->getEnvironments($projectId);
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
        return $this->client->getEnvironmentVariables($projectId, $environment);
    }

    /**
     * Get the function invocation with the given ID.
     */
    public function getInvocation(int $invocationId): Collection
    {
        return $this->client->getInvocation($invocationId);
    }

    /**
     * Get the network.
     */
    public function getNetwork(int $networkId): Collection
    {
        return $this->client->getNetwork($networkId);
    }

    /**
     * Get the networks that belong to the given team.
     */
    public function getNetworks(int $teamId): Collection
    {
        return $this->client->getNetworks($teamId);
    }

    /**
     * Get the project.
     */
    public function getProject(int $projectId): Collection
    {
        return $this->client->getProject($projectId);
    }

    /**
     * Get the projects that belong to the given team.
     */
    public function getProjects(int $teamId): Collection
    {
        return $this->client->getProjects($teamId);
    }

    /**
     * Get the cloud provider with the given ID.
     */
    public function getProvider(int $providerId): Collection
    {
        return $this->client->getProvider($providerId);
    }

    /**
     * Get the cloud providers for the given team ID.
     */
    public function getProviders(int $teamId): Collection
    {
        return $this->client->getProviders($teamId);
    }

    /**
     * Get the regions supported by the given cloud provider.
     */
    public function getRegions(int $providerId): Collection
    {
        return $this->client->getRegions($providerId);
    }

    /**
     * Get all the secrets for the given project on the given environment.
     */
    public function getSecrets(int $projectId, string $environment): Collection
    {
        return $this->client->getSecrets($projectId, $environment);
    }

    /**
     * Get the signed asset requests for the given asset files.
     */
    public function getSignedAssetRequests(int $deploymentId, array $assets): Collection
    {
        try {
            return $this->client->getSignedAssetRequests($deploymentId, $assets);
        } catch (UnexpectedApiResponseException $exception) {
            throw new RuntimeException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Get the signed asset requests for the given asset files.
     */
    public function getSignedUploadRequests(int $projectId, string $environment, array $uploads): Collection
    {
        try {
            return $this->client->getSignedUploadRequests($projectId, $environment, $uploads);
        } catch (UnexpectedApiResponseException $exception) {
            throw new RuntimeException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Get the details on the given team.
     */
    public function getTeam($teamId): Collection
    {
        return $this->client->getTeam($teamId);
    }

    /**
     * Get the teams the user is a member of.
     */
    public function getTeams(): Collection
    {
        return $this->client->getTeams();
    }

    /**
     * Change the value of the DNS record in the given DNS zone ID or name.
     */
    public function importDnsRecords($zoneIdOrName, array $subdomains = [])
    {
        $zone = $this->getDnsZone($zoneIdOrName);

        $this->client->importDnsRecords($zone['id'], $subdomains);
    }

    /**
     * Invalidate the content delivery network cache for the given project environment.
     */
    public function invalidateCache(int $projectId, string $environment, array $paths)
    {
        $this->client->invalidateCache($projectId, $environment, $paths);
    }

    /**
     * Checks if the client is authenticated with the Ymir API.
     */
    public function isAuthenticated(): bool
    {
        try {
            return $this->cliConfiguration->hasAccessToken() && !$this->getAuthenticatedUser()->isEmpty();
        } catch (ClientException $exception) {
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
        $this->client->removeBastionHost($networkId);
    }

    /**
     * Remove the NAT gateway from the given network.
     */
    public function removeNatGateway(int $networkId)
    {
        $this->client->removeNatGateway($networkId);
    }

    /**
     * Rotate the password of the given database server.
     */
    public function rotateDatabaseServerPassword(int $databaseServerId): Collection
    {
        return $this->client->rotateDatabaseServerPassword($databaseServerId);
    }

    /**
     * Rotate the password of given database user on the given database server.
     */
    public function rotateDatabaseUserPassword(int $databaseServerId, int $userId): Collection
    {
        return $this->client->rotateDatabaseUserPassword($databaseServerId, $userId);
    }

    /**
     * Send signal to the Ymir API to start the deployment.
     */
    public function startDeployment(int $deploymentId)
    {
        $this->client->startDeployment($deploymentId);
    }

    /**
     * Update the given database server.
     */
    public function updateDatabaseServer(int $databaseServerId, int $storage, string $type)
    {
        $this->client->updateDatabaseServer($databaseServerId, $storage, $type);
    }

    /**
     * Update the given cloud provider.
     */
    public function updateProvider(int $providerId, array $credentials, string $name)
    {
        $this->client->updateProvider($providerId, $credentials, $name);
    }

    /**
     * Validates the project configuration. Returns nothing if no errors were found.
     */
    public function validateProjectConfiguration(ProjectConfiguration $projectConfiguration, array $environments = [])
    {
        $this->client->validateProjectConfiguration($projectConfiguration->getProjectId(), $projectConfiguration->toArray(), $environments);
    }
}
