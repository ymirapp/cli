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
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Command\Network\AddBastionHostCommand;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Process\Process;
use Ymir\Cli\Support\Arr;
use Ymir\Cli\Tool\Ssh;

abstract class AbstractDatabaseCommand extends AbstractCommand
{
    /**
     * Determine the database server that the command is interacting with.
     */
    protected function determineDatabaseServer(string $question, Input $input, Output $output): array
    {
        $databases = $this->apiClient->getDatabaseServers($this->cliConfiguration->getActiveTeamId());
        $databaseIdOrName = $input->getStringOption('server', true);

        if ($databases->isEmpty()) {
            throw new RuntimeException(sprintf('The currently active team has no database servers. You can create one with the "%s" command.', CreateDatabaseServerCommand::NAME));
        } elseif (empty($databaseIdOrName) && 1 === $databases->count()) {
            return $databases->first();
        } elseif (empty($databaseIdOrName)) {
            $databaseIdOrName = $output->choiceWithResourceDetails($question, $databases);
        }

        // Need to improve this since you can have the same name in different regions
        $database = $databases->firstWhere('id', $databaseIdOrName) ?? $databases->firstWhere('name', $databaseIdOrName);

        if (1 < $databases->where('name', $databaseIdOrName)->count()) {
            throw new RuntimeException(sprintf('Unable to select a database server because more than one database server has the name "%s"', $databaseIdOrName));
        } elseif (empty($database['id'])) {
            throw new InvalidInputException(sprintf('Unable to find a database server with "%s" as the ID or name', $databaseIdOrName));
        }

        return $database;
    }

    /**
     * Determine the password to use to connect to the database server with the given user.
     */
    protected function determinePassword(Input $input, Output $output, string $user): string
    {
        $password = $input->getStringOption('password', true);

        if (empty($password)) {
            $password = (string) $output->askHidden(sprintf('What\'s the "<comment>%s</comment>" password?', $user));
        }

        return $password;
    }

    /**
     * Determine the user to use to connect to the database server.
     */
    protected function determineUser(Input $input, Output $output): string
    {
        $user = $input->getStringOption('user', true);

        if (empty($user)) {
            $user = $output->ask('Which user do you want to use to connect to the database server?', 'ymir');
        }

        return $user;
    }

    /**
     * Start a SSH tunnel to a private database server.
     */
    protected function startSshTunnel(array $databaseServer): Process
    {
        $network = $this->apiClient->getNetwork(Arr::get($databaseServer, 'network.id'));

        if (!is_array($network->get('bastion_host'))) {
            throw new RuntimeException(sprintf('The database server network does\'t have a bastion host to connect to. You can add one to the network with the "%s" command.', AddBastionHostCommand::NAME));
        }

        $tunnel = Ssh::tunnelBastionHost($network->get('bastion_host'), 3305, $databaseServer['endpoint'], 3306);

        // Need to wait a bit while SSH connection opens
        sleep(1);

        return $tunnel;
    }
}
