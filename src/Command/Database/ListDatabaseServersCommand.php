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

use Ymir\Cli\Command\AbstractCommand;

class ListDatabaseServersCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'database:server:list';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('List all the database servers that the current team has access to');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $this->output->table(
            ['Id', 'Name', 'Provider', 'Network', 'Region', 'Status', 'Locked', 'Public', 'Type', 'Storage'],
            $this->apiClient->getDatabaseServers($this->cliConfiguration->getActiveTeamId())->map(function (array $database) {
                return [
                    $database['id'],
                    $database['name'],
                    $database['network']['provider']['name'],
                    $database['network']['name'],
                    $database['region'],
                    $this->output->formatStatus($database['status']),
                    $this->output->formatBoolean($database['locked']),
                    $this->output->formatBoolean($database['publicly_accessible']),
                    $database['type'],
                    $database['storage'] ? $database['storage'].'GB' : 'N/A',
                ];
            })->all()
        );
    }
}
