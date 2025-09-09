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
use Ymir\Cli\Exception\ApiRuntimeException;
use Ymir\Cli\Exception\InvalidArgumentException;
use Ymir\Cli\Resource\Model;
use Ymir\Cli\Resource\ResourceCollection;
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
     * The authenticated user.
     *
     * @var Model\User|null
     */
    private $user;

    /**
     * Constructor.
     */
    public function __construct(Client $client, CliConfiguration $cliConfiguration)
    {
        $this->client = $client;
        $this->cliConfiguration = $cliConfiguration;

        if ($this->cliConfiguration->hasAccessToken()) {
            $this->setAccessToken($this->cliConfiguration->getAccessToken());
        }
    }

    /**
     * Add a bastion host to the given network.
     */
    public function addBastionHost(Model\Network $network): Model\BastionHost
    {
        return Model\BastionHost::fromArray($this->client->addBastionHost($network->getId())->all());
    }

    /**
     * Add a NAT gateway to the given network.
     */
    public function addNatGateway(Model\Network $network): void
    {
        $this->client->addNatGateway($network->getId());
    }

    /**
     * Send signal to the Ymir API to cancel the deployment.
     */
    public function cancelDeployment(Model\Deployment $deployment): void
    {
        $this->client->cancelDeployment($deployment->getId());
    }

    /**
     * Change the lock of the given database server.
     */
    public function changeDatabaseServerLock(Model\DatabaseServer $databaseServer, bool $locked): void
    {
        $this->client->changeDatabaseServerLock($databaseServer->getId(), $locked);
    }

    /**
     * Change the value of the DNS record in the given DNS zone.
     */
    public function changeDnsRecord(Model\DnsZone $zone, string $type, string $name, string $value): void
    {
        $this->client->changeDnsRecord($zone->getId(), $type, $name, $value);
    }

    /**
     * Change the environment variables of the given project environment.
     */
    public function changeEnvironmentVariables(Model\Project $project, Model\Environment $environment, array $variables, bool $overwrite = false): void
    {
        $this->client->changeEnvironmentVariables($project->getId(), $environment->getName(), $variables, $overwrite);
    }

    /**
     * Change the value of the given secret for the given project environment.
     */
    public function changeSecret(Model\Project $project, Model\Environment $environment, string $name, string $value): void
    {
        $this->client->changeSecret($project->getId(), $environment->getName(), $name, $value);
    }

    /**
     * Create a new cache on the given network.
     */
    public function createCache(Model\Network $network, string $name, string $engine, string $type): Model\CacheCluster
    {
        return Model\CacheCluster::fromArray($this->client->createCache($network->getId(), $name, $engine, $type)->all());
    }

    /**
     * Create a new SSL certificate.
     */
    public function createCertificate(Model\CloudProvider $provider, array $domains, string $region): Model\Certificate
    {
        return Model\Certificate::fromArray($this->client->createCertificate($provider->getId(), $domains, $region)->all());
    }

    /**
     * Create a new database on the given database server.
     */
    public function createDatabase(Model\DatabaseServer $databaseServer, string $name): Model\Database
    {
        $this->client->createDatabase($databaseServer->getId(), $name);

        return new Model\Database($name, $databaseServer);
    }

    /**
     * Create a new database on the given network.
     */
    public function createDatabaseServer(Model\Network $network, string $name, string $type, ?int $storage = 50, bool $public = false): Model\DatabaseServer
    {
        return Model\DatabaseServer::fromArray($this->client->createDatabaseServer($network->getId(), $name, $type, $storage, $public)->all());
    }

    /**
     * Create a new user on the given database server.
     */
    public function createDatabaseUser(Model\DatabaseServer $databaseServer, string $username, array $databases = []): Model\DatabaseUser
    {
        return Model\DatabaseUser::fromArray($this->client->createDatabaseUser($databaseServer->getId(), $username, $databases)->all());
    }

    /**
     * Create a new deployment for the given project on the given environment.
     */
    public function createDeployment(Model\Project $project, Model\Environment $environment, array $configuration, ?string $assetsHash = null): Model\Deployment
    {
        return Model\Deployment::fromArray($this->client->createDeployment($project->getId(), $environment->getName(), $configuration, $assetsHash)->all());
    }

    /**
     * Create a new DNS zone on the given cloud provider.
     */
    public function createDnsZone(Model\CloudProvider $provider, string $name): Model\DnsZone
    {
        return Model\DnsZone::fromArray($this->client->createDnsZone($provider->getId(), $name)->all());
    }

    /**
     * Create a new email identity on the given cloud provider.
     */
    public function createEmailIdentity(Model\CloudProvider $provider, string $name, string $region): Model\EmailIdentity
    {
        return Model\EmailIdentity::fromArray($this->client->createEmailIdentity($provider->getId(), $name, $region)->all());
    }

    /**
     * Create a new environment with the given name for the given project.
     */
    public function createEnvironment(Model\Project $project, string $name): Model\Environment
    {
        return Model\Environment::fromArray($this->client->createEnvironment($project->getId(), $name)->all());
    }

    /**
     * Create a new function invocation for the given project on the given environment.
     */
    public function createInvocation(Model\Project $project, Model\Environment $environment, array $payload): Collection
    {
        return $this->client->createInvocation($project->getId(), $environment->getName(), $payload);
    }

    /**
     * Create a new network.
     */
    public function createNetwork(Model\CloudProvider $provider, string $name, string $region): Model\Network
    {
        return Model\Network::fromArray($this->client->createNetwork($provider->getId(), $name, $region)->all());
    }

    /**
     * Create a new project with the given cloud provider.
     */
    public function createProject(Model\CloudProvider $provider, string $name, string $region, array $environments = []): Model\Project
    {
        return Model\Project::fromArray($this->client->createProject($provider->getId(), $name, $region, $environments)->all());
    }

    /**
     * Create a new cloud provider with the given name and credentials.
     */
    public function createProvider(Model\Team $team, string $name, array $credentials): Model\CloudProvider
    {
        return Model\CloudProvider::fromArray($this->client->createProvider($team->getId(), $name, $credentials)->all());
    }

    /**
     * Create a new deployment redeploying the given project on the given environment.
     */
    public function createRedeployment(Model\Project $project, Model\Environment $environment): Model\Deployment
    {
        return Model\Deployment::fromArray($this->client->createRedeployment($project->getId(), $environment->getName())->all());
    }

    /**
     * Create a new deployment to rollback to the given deployment.
     */
    public function createRollback(Model\Project $project, Model\Environment $environment, Model\Deployment $deployment): Model\Deployment
    {
        return Model\Deployment::fromArray($this->client->createRollback($project->getId(), $environment->getName(), $deployment->getId())->all());
    }

    /**
     * Create a new team with the given name.
     */
    public function createTeam(string $name): Model\Team
    {
        return Model\Team::fromArray($this->client->createTeam($name)->all());
    }

    /**
     * Delete the given cache.
     */
    public function deleteCache(Model\CacheCluster $cache): void
    {
        $this->client->deleteCache($cache->getId());
    }

    /**
     * Delete the given SSL certificate.
     */
    public function deleteCertificate(Model\Certificate $certificate): void
    {
        $this->client->deleteCertificate($certificate->getId());
    }

    /**
     * Delete the given database on the given database server.
     */
    public function deleteDatabase(Model\Database $database): void
    {
        $this->client->deleteDatabase($database->getDatabaseServer()->getId(), $database->getName());
    }

    /**
     * Delete the given database server.
     */
    public function deleteDatabaseServer(Model\DatabaseServer $databaseServer): void
    {
        $this->client->deleteDatabaseServer($databaseServer->getId());
    }

    /**
     * Delete the given database user on the given database server.
     */
    public function deleteDatabaseUser(Model\DatabaseUser $databaseUser): void
    {
        $this->client->deleteDatabaseUser($databaseUser->getDatabaseServer()->getId(), $databaseUser->getId());
    }

    /**
     * Delete the given DNS record.
     */
    public function deleteDnsRecord(Model\DnsZone $zone, Model\DnsRecord $record): void
    {
        $this->client->deleteDnsRecord($zone->getId(), $record->getId());
    }

    /**
     * Delete the given DNS zone.
     */
    public function deleteDnsZone(Model\DnsZone $zone): void
    {
        $this->client->deleteDnsZone($zone->getId());
    }

    /**
     * Delete the given email identity.
     */
    public function deleteEmailIdentity(Model\EmailIdentity $identity): void
    {
        $this->client->deleteEmailIdentity($identity->getId());
    }

    /**
     * Delete the given environment on the given project.
     */
    public function deleteEnvironment(Model\Project $project, Model\Environment $environment, bool $deleteResources = false): void
    {
        $this->client->deleteEnvironment($project->getId(), $environment->getName(), $deleteResources);
    }

    /**
     * Delete the given network.
     */
    public function deleteNetwork(Model\Network $network): void
    {
        $this->client->deleteNetwork($network->getId());
    }

    /**
     * Delete the given project.
     */
    public function deleteProject(Model\Project $project, bool $deleteResources = false): void
    {
        $this->client->deleteProject($project->getId(), $deleteResources);
    }

    /**
     * Delete the given cloud provider.
     */
    public function deleteProvider(Model\CloudProvider $provider): void
    {
        $this->client->deleteProvider($provider->getId());
    }

    /**
     * Delete the given secret.
     */
    public function deleteSecret(Model\Secret $secret): void
    {
        $this->client->deleteSecret($secret->getId());
    }

    /**
     * Get an access token for the given email and password.
     */
    public function getAccessToken(string $email, string $password, ?string $authenticationCode = null): string
    {
        try {
            return $this->client->getAccessToken($email, $password, $authenticationCode);
        } catch (UnexpectedApiResponseException $exception) {
            throw new ApiRuntimeException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Get the user's currently active team.
     */
    public function getActiveTeam(): Model\Team
    {
        return Model\Team::fromArray($this->client->getActiveTeam()->all());
    }

    /**
     * Get the upload URL for the artifact file.
     */
    public function getArtifactUploadUrl(Model\Deployment $deployment): string
    {
        try {
            return $this->client->getArtifactUploadUrl($deployment->getId());
        } catch (UnexpectedApiResponseException $exception) {
            throw new ApiRuntimeException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Get the authenticated user.
     */
    public function getAuthenticatedUser(): ?Model\User
    {
        try {
            if (!$this->user instanceof Model\User) {
                $this->user = Model\User::fromArray($this->client->getAuthenticatedUser()->all());
            }
        } catch (ClientException $exception) {
            if (401 !== $exception->getCode()) {
                throw $exception;
            }
        } catch (InvalidArgumentException $exception) {
        }

        return $this->user;
    }

    /**
     * Get the bastion host with the given ID.
     */
    public function getBastionHost(int $bastionHostId): Model\BastionHost
    {
        return Model\BastionHost::fromArray($this->client->getBastionHost($bastionHostId)->all());
    }

    /**
     * Get the caches that belong to the given team.
     */
    public function getCaches(Model\Team $team): ResourceCollection
    {
        return (new ResourceCollection($this->client->getCaches($team->getId())))->mapWithKeys(function (array $cache) {
            $cache = Model\CacheCluster::fromArray($cache);

            return [$cache->getName() => $cache];
        });
    }

    /**
     * Get the types of cache available on the given cloud provider.
     */
    public function getCacheTypes(Model\CloudProvider $provider): Collection
    {
        try {
            return $this->client->getCacheTypes($provider->getId());
        } catch (UnexpectedApiResponseException $exception) {
            throw new ApiRuntimeException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Get the SSL certificates with the given ID.
     */
    public function getCertificate(int $certificateId): Model\Certificate
    {
        return Model\Certificate::fromArray($this->client->getCertificate($certificateId)->all());
    }

    /**
     * Get the SSL certificates that belong to the given team.
     */
    public function getCertificates(Model\Team $team): ResourceCollection
    {
        return (new ResourceCollection($this->client->getCertificates($team->getId())))->map(function (array $certificate): Model\Certificate {
            return Model\Certificate::fromArray($certificate);
        });
    }

    /**
     * Get the list of databases on the given database server.
     */
    public function getDatabases(Model\DatabaseServer $databaseServer): ResourceCollection
    {
        return (new ResourceCollection($this->client->getDatabases($databaseServer->getId())))->mapWithKeys(function (string $name) use ($databaseServer) {
            $database = new Model\Database($name, $databaseServer);

            return [$database->getName() => $database];
        });
    }

    /**
     * Get the information on the database server with the given database ID or name.
     */
    public function getDatabaseServer(int $databaseServerId): Model\DatabaseServer
    {
        return Model\DatabaseServer::fromArray($this->client->getDatabaseServer($databaseServerId)->all());
    }

    /**
     * Get the database servers that belong to the given team.
     */
    public function getDatabaseServers(Model\Team $team): ResourceCollection
    {
        return (new ResourceCollection($this->client->getDatabaseServers($team->getId())))->mapWithKeys(function (array $databaseServer) {
            $databaseServer = Model\DatabaseServer::fromArray($databaseServer);

            return [$databaseServer->getName() => $databaseServer];
        });
    }

    /**
     * Get the types of database server available on the given cloud provider.
     */
    public function getDatabaseServerTypes(Model\CloudProvider $provider): Collection
    {
        try {
            return $this->client->getDatabaseServerTypes($provider->getId());
        } catch (UnexpectedApiResponseException $exception) {
            throw new ApiRuntimeException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Get the list of database users on the given database server.
     */
    public function getDatabaseUsers(Model\DatabaseServer $databaseServer): ResourceCollection
    {
        return (new ResourceCollection($this->client->getDatabaseUsers($databaseServer->getId())))->mapWithKeys(function (array $databaseUser) {
            $databaseUser = Model\DatabaseUser::fromArray($databaseUser);

            return [$databaseUser->getName() => $databaseUser];
        });
    }

    /**
     * Get the details on the given deployment.
     */
    public function getDeployment(int $deploymentId): Model\Deployment
    {
        return Model\Deployment::fromArray($this->client->getDeployment($deploymentId)->all());
    }

    /**
     * Get the container image used by the deployment.
     */
    public function getDeploymentImage(Model\Deployment $deployment): Collection
    {
        return $this->client->getDeploymentImage($deployment->getId());
    }

    /**
     * Get all the deployments for the given project on the given environment.
     */
    public function getDeployments(Model\Project $project, Model\Environment $environment): ResourceCollection
    {
        return (new ResourceCollection($this->client->getDeployments($project->getId(), $environment->getName())))->map(function (array $deployment): Model\Deployment {
            return Model\Deployment::fromArray($deployment);
        });
    }

    /**
     * Get the DNS records belonging to the given DNS zone.
     */
    public function getDnsRecords(Model\DnsZone $zone): ResourceCollection
    {
        return (new ResourceCollection($this->client->getDnsRecords($zone->getId())))->map(function (array $record): Model\DnsRecord {
            return Model\DnsRecord::fromArray($record);
        });
    }

    /**
     * Get the DNS zone information from the given zone ID.
     */
    public function getDnsZone(int $zoneId): Model\DnsZone
    {
        return Model\DnsZone::fromArray($this->client->getDnsZone($zoneId)->toArray());
    }

    /**
     * Get the DNS zones that belong to the given team.
     */
    public function getDnsZones(Model\Team $team): ResourceCollection
    {
        return (new ResourceCollection($this->client->getDnsZones($team->getId())))->map(function (array $zone): Model\DnsZone {
            return Model\DnsZone::fromArray($zone);
        });
    }

    /**
     * Get the email identities that belong to the given team.
     */
    public function getEmailIdentities(Model\Team $team): ResourceCollection
    {
        return (new ResourceCollection($this->client->getEmailIdentities($team->getId())))->map(function (array $identity): Model\EmailIdentity {
            return Model\EmailIdentity::fromArray($identity);
        });
    }

    /**
     * Get the details on the given email identity.
     */
    public function getEmailIdentity(int $identityId): Model\EmailIdentity
    {
        return Model\EmailIdentity::fromArray($this->client->getEmailIdentity($identityId)->all());
    }

    /**
     * Get the project environment details.
     */
    public function getEnvironment(Model\Project $project, string $environment): Model\Environment
    {
        return Model\Environment::fromArray($this->client->getEnvironment($project->getId(), $environment)->all());
    }

    /**
     * Get the recent logs from a project environment's function.
     */
    public function getEnvironmentLogs(Model\Project $project, Model\Environment $environment, string $function, int $since, ?string $order = null): Collection
    {
        return $this->client->getEnvironmentLogs($project->getId(), $environment->getName(), $function, $since, $order);
    }

    /**
     * Get the project environment's metrics.
     */
    public function getEnvironmentMetrics(Model\Project $project, Model\Environment $environment, string $period): Collection
    {
        return $this->client->getEnvironmentMetrics($project->getId(), $environment->getName(), $period);
    }

    /**
     * Get the details on the project's environments.
     */
    public function getEnvironments(Model\Project $project): ResourceCollection
    {
        return (new ResourceCollection($this->client->getEnvironments($project->getId())))->mapWithKeys(function (array $environment) {
            $environment = Model\Environment::fromArray($environment);

            return [$environment->getName() => $environment];
        });
    }

    /**
     * Get the project environment's vanity domain name.
     */
    public function getEnvironmentVanityDomainName(Model\Project $project, Model\Environment $environment): string
    {
        return $environment->getVanityDomainName();
    }

    /**
     * Get the environment variables of the given project environment.
     */
    public function getEnvironmentVariables(Model\Project $project, Model\Environment $environment): Collection
    {
        return $this->client->getEnvironmentVariables($project->getId(), $environment->getName());
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
    public function getNetwork(int $networkId): Model\Network
    {
        return Model\Network::fromArray($this->client->getNetwork($networkId)->all());
    }

    /**
     * Get the networks that belong to the given team.
     */
    public function getNetworks(Model\Team $team): ResourceCollection
    {
        return (new ResourceCollection($this->client->getNetworks($team->getId())))->map(function (array $network): Model\Network {
            return Model\Network::fromArray($network);
        });
    }

    /**
     * Get the project.
     */
    public function getProject(int $projectId): Model\Project
    {
        return Model\Project::fromArray($this->client->getProject($projectId)->all());
    }

    /**
     * Get the projects that belong to the given team.
     */
    public function getProjects(Model\Team $team): ResourceCollection
    {
        return (new ResourceCollection($this->client->getProjects($team->getId())))->mapWithKeys(function (array $project) {
            $project = Model\Project::fromArray($project);

            return [$project->getName() => $project];
        });
    }

    /**
     * Get the cloud provider with the given ID.
     */
    public function getProvider(int $providerId): Model\CloudProvider
    {
        return Model\CloudProvider::fromArray($this->client->getProvider($providerId)->all());
    }

    /**
     * Get the cloud providers for the given team ID.
     */
    public function getProviders(Model\Team $team): ResourceCollection
    {
        return (new ResourceCollection($this->client->getProviders($team->getId())))->map(function (array $provider): Model\CloudProvider {
            return Model\CloudProvider::fromArray($provider);
        });
    }

    /**
     * Get the regions supported by the given cloud provider.
     */
    public function getRegions(Model\CloudProvider $provider): Collection
    {
        return $this->client->getRegions($provider->getId());
    }

    /**
     * Get all the secrets for the given project on the given environment.
     */
    public function getSecrets(Model\Project $project, Model\Environment $environment): ResourceCollection
    {
        return (new ResourceCollection($this->client->getSecrets($project->getId(), $environment->getName())))->map(function (array $secret): Model\Secret {
            return Model\Secret::fromArray($secret);
        });
    }

    /**
     * Get the signed asset requests for the given asset files.
     */
    public function getSignedAssetRequests(Model\Deployment $deployment, array $assets): Collection
    {
        try {
            return $this->client->getSignedAssetRequests($deployment->getId(), $assets);
        } catch (UnexpectedApiResponseException $exception) {
            throw new ApiRuntimeException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Get the signed asset requests for the given asset files.
     */
    public function getSignedUploadRequests(Model\Project $project, Model\Environment $environment, array $uploads): Collection
    {
        try {
            return $this->client->getSignedUploadRequests($project->getId(), $environment->getName(), $uploads);
        } catch (UnexpectedApiResponseException $exception) {
            throw new ApiRuntimeException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Get the details on the given team.
     */
    public function getTeam(int $teamId): Model\Team
    {
        return Model\Team::fromArray($this->client->getTeam($teamId)->all());
    }

    /**
     * Get the teams the user is a member of.
     */
    public function getTeams(): ResourceCollection
    {
        return (new ResourceCollection($this->client->getTeams()))->map(function (array $team): Model\Team {
            return Model\Team::fromArray($team);
        });
    }

    /**
     * Change the value of the DNS record in the given DNS zone.
     */
    public function importDnsRecords(Model\DnsZone $zone, array $subdomains = []): void
    {
        $this->client->importDnsRecords($zone->getId(), $subdomains);
    }

    /**
     * Invalidate the content delivery network cache for the given project environment.
     */
    public function invalidateCache(Model\Project $project, Model\Environment $environment, array $paths): void
    {
        $this->client->invalidateCache($project->getId(), $environment->getName(), $paths);
    }

    /**
     * Checks if the client is authenticated with the Ymir API.
     */
    public function isAuthenticated(): bool
    {
        return $this->getAuthenticatedUser() instanceof Model\User;
    }

    /**
     * Remove the bastion host from the given network.
     */
    public function removeBastionHost(Model\Network $network): void
    {
        $this->client->removeBastionHost($network->getId());
    }

    /**
     * Remove the NAT gateway from the given network.
     */
    public function removeNatGateway(Model\Network $network): void
    {
        $this->client->removeNatGateway($network->getId());
    }

    /**
     * Rotate the password of the given database server.
     */
    public function rotateDatabaseServerPassword(Model\DatabaseServer $databaseServer): Collection
    {
        return $this->client->rotateDatabaseServerPassword($databaseServer->getId());
    }

    /**
     * Rotate the password of given database user on the given database server.
     */
    public function rotateDatabaseUserPassword(Model\DatabaseUser $databaseUser): Collection
    {
        return $this->client->rotateDatabaseUserPassword($databaseUser->getDatabaseServer()->getId(), $databaseUser->getId());
    }

    /**
     * Set the Ymir API access token.
     */
    public function setAccessToken(string $token): void
    {
        $this->user = null;

        $this->client->setAccessToken($token);
    }

    /**
     * Send signal to the Ymir API to start the deployment.
     */
    public function startDeployment(Model\Deployment $deployment): void
    {
        $this->client->startDeployment($deployment->getId());
    }

    /**
     * Update the given cache cluster.
     */
    public function updateCache(Model\CacheCluster $cache, string $type): void
    {
        $this->client->updateCache($cache->getId(), $type);
    }

    /**
     * Update the given database server.
     */
    public function updateDatabaseServer(Model\DatabaseServer $databaseServer, int $storage, string $type): void
    {
        $this->client->updateDatabaseServer($databaseServer->getId(), $storage, $type);
    }

    /**
     * Update the given cloud provider.
     */
    public function updateProvider(Model\CloudProvider $provider, array $credentials, string $name): void
    {
        $this->client->updateProvider($provider->getId(), $credentials, $name);
    }

    /**
     * Validates the project configuration. Returns nothing if no errors were found.
     */
    public function validateProjectConfiguration(Model\Project $project, array $configuration, array $environments = []): Collection
    {
        return $this->client->validateProjectConfiguration($project->getId(), $configuration, $environments);
    }
}
