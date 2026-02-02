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

namespace Ymir\Cli\Resource\Definition;

use Ymir\Cli\ApiClient;
use Ymir\Cli\Command\Database\CreateDatabaseServerCommand;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Resource\NoResourcesFoundException;
use Ymir\Cli\Exception\Resource\ResourceNotFoundException;
use Ymir\Cli\Exception\Resource\ResourceResolutionException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\Model\ResourceModelInterface;
use Ymir\Cli\Resource\Requirement\DatabaseServerStorageRequirement;
use Ymir\Cli\Resource\Requirement\DatabaseServerTypeRequirement;
use Ymir\Cli\Resource\Requirement\NameSlugRequirement;
use Ymir\Cli\Resource\Requirement\PrivateDatabaseServerRequirement;
use Ymir\Cli\Resource\Requirement\ResolveOrProvisionNetworkRequirement;

class DatabaseServerDefinition implements ProvisionableResourceDefinitionInterface, ResolvableResourceDefinitionInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getModelClass(): string
    {
        return DatabaseServer::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequirements(): array
    {
        return [
            'name' => new NameSlugRequirement('What is the name of the database server being created?'),
            'network' => new ResolveOrProvisionNetworkRequirement('Which network should the database server be created on?'),
            'type' => new DatabaseServerTypeRequirement('Which type should the database server be?'),
            'storage' => new DatabaseServerStorageRequirement('How much storage should the database server have?', '50'),
            'private' => new PrivateDatabaseServerRequirement(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceName(): string
    {
        return 'database server';
    }

    /**
     * {@inheritdoc}
     */
    public function provision(ApiClient $apiClient, array $fulfilledRequirements): ?ResourceModelInterface
    {
        return $apiClient->createDatabaseServer($fulfilledRequirements['network'], $fulfilledRequirements['name'], $fulfilledRequirements['type'], $fulfilledRequirements['storage'], !$fulfilledRequirements['private']);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ExecutionContext $context, string $question, array $fulfilledRequirements = []): DatabaseServer
    {
        $input = $context->getInput();
        $serverIdOrName = null;

        if ($input->hasArgument('server')) {
            $serverIdOrName = $input->getStringArgument('server');
        } elseif ($input->hasOption('server')) {
            $serverIdOrName = $input->getStringOption('server', true);
        }

        $servers = $context->getApiClient()->getDatabaseServers($context->getTeam());

        if (!empty($fulfilledRequirements['region'])) {
            $servers = $servers->filter(function (DatabaseServer $databaseServer) use ($fulfilledRequirements) {
                return $databaseServer->getRegion() === $fulfilledRequirements['region'];
            });
        }

        if ($servers->isEmpty()) {
            throw new NoResourcesFoundException(sprintf('The currently active team has no database servers, but you can create one with the "%s" command', CreateDatabaseServerCommand::NAME));
        } elseif (empty($serverIdOrName)) {
            $serverIdOrName = $context->getOutput()->choiceWithResourceDetails($question, $servers);
        }

        if (empty($serverIdOrName)) {
            throw new InvalidInputException('You must provide a valid database server ID or name');
        } elseif (!is_numeric($serverIdOrName) && 1 < $servers->whereIdOrName($serverIdOrName)->count()) {
            throw new ResourceResolutionException(sprintf('Unable to select a database server because more than one database server has the name "%s"', $serverIdOrName));
        }

        $resolvedDatabaseServer = $servers->firstWhereIdOrName($serverIdOrName);

        if (!$resolvedDatabaseServer instanceof DatabaseServer) {
            throw new ResourceNotFoundException($this->getResourceName(), $serverIdOrName);
        }

        return $resolvedDatabaseServer;
    }
}
