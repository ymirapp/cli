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

namespace Ymir\Cli\Project\Initialization;

use Illuminate\Support\Collection;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Project\Configuration\ConfigurationChangeInterface;
use Ymir\Cli\Project\Configuration\DatabaseConfigurationChange;
use Ymir\Cli\Resource\Model\DatabaseServer;

class DatabaseInitializationStep implements InitializationStepInterface
{
    /**
     * {@inheritDoc}
     */
    public function perform(ExecutionContext $context, array $projectRequirements): ?ConfigurationChangeInterface
    {
        $databaseServer = $this->selectDatabaseServer($context, $projectRequirements['region']);

        if (!$databaseServer instanceof DatabaseServer) {
            return null;
        }

        $prefix = trim($context->getOutput()->askSlug('Which database prefix should the project have?', sprintf('%s_', $projectRequirements['name'])));

        $this->createDatabases($context, $databaseServer, collect($projectRequirements['environments'])->map(function (string $environment) use ($prefix): string {
            return $prefix.$environment;
        }));

        return new DatabaseConfigurationChange($databaseServer->getName(), $prefix);
    }

    /**
     * Create the given databases on the given database server.
     */
    private function createDatabases(ExecutionContext $context, DatabaseServer $databaseServer, Collection $databases): void
    {
        $output = $context->getOutput();

        $joinedNames = $databases->map(function (string $database): string {
            return sprintf('"<comment>%s</comment>"', $database);
        })->join(', ', ' and ');
        $resourceType = $databases->count() > 1 ? 'databases' : 'database';
        $serverName = sprintf('<comment>%s</comment>', $databaseServer->getName());

        if (!$databaseServer->isPublic()) {
            $output->warning(sprintf('You must create the %s %s manually because the %s database server is private', $joinedNames, $resourceType, $serverName));
        } elseif ($output->confirm(sprintf('Would you like to create the %s %s for your project on the %s database server?', $joinedNames, $resourceType, $serverName))) {
            $databases->each(function (string $database) use ($context, $databaseServer): void {
                $context->getApiClient()->createDatabase($databaseServer, $database);
            });
        }
    }

    /**
     * Select or provision a database server in the given region.
     */
    private function selectDatabaseServer(ExecutionContext $context, string $region): ?DatabaseServer
    {
        $apiClient = $context->getApiClient();
        $output = $context->getOutput();
        $databaseServer = null;

        $databaseServers = $apiClient->getDatabaseServers($context->getTeam())->filter(function (DatabaseServer $databaseServer) use ($region): bool {
            return $region === $databaseServer->getRegion();
        })->filter(function (DatabaseServer $databaseServer): bool {
            return !in_array($databaseServer->getStatus(), ['deleting', 'failed']);
        });

        if (!$databaseServers->isEmpty() && $output->confirm('Would you like to use an existing database server for this project?')) {
            $databaseServer = $databaseServers->firstWhereIdOrName($output->choiceWithResourceDetails('Which database server would you like to use?', $databaseServers));
        } elseif (
            (!$databaseServers->isEmpty() && $output->confirm('Would you like to create a new one for this project instead?'))
            || ($databaseServers->isEmpty() && $output->confirm(sprintf('Your team doesn\'t have any configured database servers in the "<comment>%s</comment>" region. Would you like to create one for this team first?', $region)))
        ) {
            $databaseServer = $context->provision(DatabaseServer::class, ['region' => $region]);
        }

        return $databaseServer instanceof DatabaseServer ? $databaseServer : null;
    }
}
