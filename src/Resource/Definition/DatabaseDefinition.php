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
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\LogicException;
use Ymir\Cli\Exception\Resource\NoResourcesFoundException;
use Ymir\Cli\Exception\Resource\ResourceNotFoundException;
use Ymir\Cli\Exception\Resource\ResourceStateException;
use Ymir\Cli\Exception\UnsupportedDatabaseServerEngineException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\Database;
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\Model\ResourceModelInterface;
use Ymir\Cli\Resource\Requirement\ResolveOrProvisionPublicDatabaseServerRequirement;
use Ymir\Cli\Resource\Requirement\StringArgumentRequirement;

class DatabaseDefinition implements ProvisionableResourceDefinitionInterface, ResolvableResourceDefinitionInterface
{
    /**
     * The system database names for each database server engine.
     *
     * @var array
     */
    private const SYSTEM_DATABASES = [
        DatabaseServer::ENGINE_MYSQL => ['information_schema', 'innodb', 'mysql', 'performance_schema', 'sys'],
        DatabaseServer::ENGINE_POSTGRESQL => ['postgres', 'rdsadmin', 'template0', 'template1'],
    ];

    /**
     * {@inheritdoc}
     */
    public static function getModelClass(): string
    {
        return Database::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequirements(): array
    {
        return [
            'server' => new ResolveOrProvisionPublicDatabaseServerRequirement('Which database server should the database be created on?'),
            'database' => new StringArgumentRequirement('database', 'What is the name of the database being created?'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceName(): string
    {
        return 'database';
    }

    /**
     * {@inheritdoc}
     */
    public function provision(ApiClient $apiClient, array $fulfilledRequirements): ?ResourceModelInterface
    {
        return $apiClient->createDatabase($fulfilledRequirements['server'], $fulfilledRequirements['database']);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ExecutionContext $context, string $question, array $fulfilledRequirements = []): Database
    {
        $databaseServer = $context->getParentResource();

        if (!$databaseServer instanceof DatabaseServer) {
            throw new LogicException('A DatabaseServer must be resolved and passed into the context before resolving a database');
        }

        $databaseName = $context->getInput()->getStringArgument('database');

        if (!$databaseServer->isPublic() && empty($databaseName)) {
            throw new ResourceStateException('You must specify the database name for a private database server');
        } elseif (!$databaseServer->isPublic()) {
            return new Database($databaseName, $databaseServer);
        }

        $databases = $context->getApiClient()->getDatabases($databaseServer)->filter(function (Database $database) use ($databaseServer): bool {
            return !$this->isSystemDatabase($databaseServer, $database);
        })->values();

        if ($databases->isEmpty()) {
            throw new NoResourcesFoundException(sprintf('The "%s" database server has no databases that can be managed by Ymir', $databaseServer->getName()));
        }

        if (empty($databaseName)) {
            $databaseName = $context->getOutput()->choice($question, $databases->map(function (Database $database) {
                return $database->getName();
            }));
        }

        if (empty($databaseName)) {
            throw new InvalidInputException('You must provide a valid database name');
        }

        $resolvedDatabase = $databases->firstWhereName($databaseName);

        if (!$resolvedDatabase instanceof Database) {
            throw new ResourceNotFoundException($this->getResourceName(), $databaseName);
        }

        return $resolvedDatabase;
    }

    /**
     * Check if the database is a system database.
     */
    private function isSystemDatabase(DatabaseServer $databaseServer, Database $database): bool
    {
        $engine = $databaseServer->getEngine();

        if (!isset(self::SYSTEM_DATABASES[$engine])) {
            throw new UnsupportedDatabaseServerEngineException($engine);
        }

        return in_array($database->getName(), self::SYSTEM_DATABASES[$engine], true);
    }
}
