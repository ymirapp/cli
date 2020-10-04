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

namespace Ymir\Cli\Command\Database;

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\OutputStyle;

abstract class AbstractDatabaseCommand extends AbstractCommand
{
    /**
     * Determine the database server that the command is interacting with.
     */
    protected function determineDatabaseServer(string $question, InputInterface $input, OutputStyle $output): array
    {
        $database = null;
        $databases = $this->apiClient->getDatabaseServers($this->cliConfiguration->getActiveTeamId());
        $databaseIdOrName = $this->getStringArgument($input, 'database');

        if ($databases->isEmpty()) {
            throw new RuntimeException(sprintf('The currently active team has no database servers. You can create one with the "%s" command.', CreateDatabaseServerCommand::NAME));
        }

        $availableDatabases = $databases->where('status', 'available');

        if ($availableDatabases->isEmpty()) {
            throw new RuntimeException(sprintf('The currently active team has no available database servers. You can either create a new one with the "%s" command or wait for a database server to become available.', CreateDatabaseServerCommand::NAME));
        } elseif (empty($databaseIdOrName) && 1 === $availableDatabases->count()) {
            $databaseIdOrName = (string) $availableDatabases->pluck('id')->first();
        } elseif (empty($databaseIdOrName) && 1 < $availableDatabases->count()) {
            $databaseIdOrName = (string) $output->choice($question, $availableDatabases->pluck('name')->all());
        }

        if (is_numeric($databaseIdOrName)) {
            $database = $databases->firstWhere('id', $databaseIdOrName);
        } elseif (is_string($databaseIdOrName)) {
            $database = $databases->firstWhere('name', $databaseIdOrName);
        }

        if (empty($database['id'])) {
            throw new RuntimeException(sprintf('Unable to find a database server with "%s" as the ID or name', $databaseIdOrName));
        } elseif (isset($database['status']) && 'available' !== $database['status']) {
            throw new RuntimeException(sprintf('The database server with the ID or name "%s" is\'t available', $databaseIdOrName));
        }

        return $database;
    }
}
