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
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\Model\DatabaseUser;
use Ymir\Cli\Resource\Model\ResourceModelInterface;
use Ymir\Cli\Resource\Requirement\DatabasesRequirement;
use Ymir\Cli\Resource\Requirement\ParentDatabaseServerRequirement;
use Ymir\Cli\Resource\Requirement\StringArgumentRequirement;

class DatabaseUserDefinition implements ProvisionableResourceDefinitionInterface, ResolvableResourceDefinitionInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getModelClass(): string
    {
        return DatabaseUser::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequirements(): array
    {
        return [
            'server' => new ParentDatabaseServerRequirement(),
            'user' => new StringArgumentRequirement('user', 'What is the name of the database user being created?'),
            'databases' => new DatabasesRequirement(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceName(): string
    {
        return 'database user';
    }

    /**
     * {@inheritdoc}
     */
    public function provision(ApiClient $apiClient, array $fulfilledRequirements): ?ResourceModelInterface
    {
        return $apiClient->createDatabaseUser($fulfilledRequirements['server'], $fulfilledRequirements['user'], $fulfilledRequirements['databases']);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ExecutionContext $context, string $question): DatabaseUser
    {
        $databaseServer = $context->getParentResource();

        if (!$databaseServer instanceof DatabaseServer) {
            throw new LogicException('A DatabaseServer must be resolved and passed into the context before resolving a database user');
        }

        $databaseUsers = $context->getApiClient()->getDatabaseUsers($databaseServer)->values();

        if ($databaseUsers->isEmpty()) {
            throw new NoResourcesFoundException(sprintf('The "%s" database server doesn\'t have any managed database users', $databaseServer->getName()));
        }

        $username = $context->getInput()->getStringArgument('user');

        if (empty($username)) {
            $username = $context->getOutput()->choice($question, $databaseUsers->map(function (DatabaseUser $databaseUser) {
                return $databaseUser->getName();
            }));
        }

        if (empty($username)) {
            throw new InvalidInputException('You must provide a valid database server username');
        }

        $resolvedDatabaseUser = $databaseUsers->firstWhereName($username);

        if (!$resolvedDatabaseUser instanceof DatabaseUser) {
            throw new ResourceNotFoundException($this->getResourceName(), $username);
        }

        return $resolvedDatabaseUser;
    }
}
