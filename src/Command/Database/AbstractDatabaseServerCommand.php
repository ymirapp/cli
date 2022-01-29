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
use Ymir\Cli\Console\OutputInterface;

abstract class AbstractDatabaseServerCommand extends AbstractCommand
{
    /**
     * Determine the database server that the command is interacting with.
     */
    protected function determineDatabaseServer(string $question, InputInterface $input, OutputInterface $output): array
    {
        $databases = $this->apiClient->getDatabaseServers($this->cliConfiguration->getActiveTeamId());
        $databaseIdOrName = $this->getStringArgument($input, 'server');

        if ($databases->isEmpty()) {
            throw new RuntimeException(sprintf('The currently active team has no database servers. You can create one with the "%s" command.', CreateDatabaseServerCommand::NAME));
        } elseif (empty($databaseIdOrName)) {
            $databaseIdOrName = $output->choiceWithResourceDetails($question, $databases);
        }

        $database = $databases->firstWhere('id', $databaseIdOrName) ?? $databases->firstWhere('name', $databaseIdOrName);

        if (1 < $databases->where('name', $databaseIdOrName)->count()) {
            throw new RuntimeException(sprintf('Unable to select a database server because more than one database server has the name "%s"', $databaseIdOrName));
        } elseif (empty($database['id'])) {
            throw new RuntimeException(sprintf('Unable to find a database server with "%s" as the ID or name', $databaseIdOrName));
        }

        return $database;
    }
}
