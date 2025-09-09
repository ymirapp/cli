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
use Ymir\Cli\Resource\Model\DatabaseServer;

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
            $this->apiClient->getDatabaseServers($this->getTeam())->map(function (DatabaseServer $databaseServer) {
                return [
                    $databaseServer->getId(),
                    $databaseServer->getName(),
                    $databaseServer->getNetwork()->getProvider()->getName(),
                    $databaseServer->getNetwork()->getName(),
                    $databaseServer->getRegion(),
                    $this->output->formatStatus($databaseServer->getStatus()),
                    $this->output->formatBoolean($databaseServer->isLocked()),
                    $this->output->formatBoolean($databaseServer->isPublic()),
                    $databaseServer->getType(),
                    $databaseServer->getStorage() ? $databaseServer->getStorage().'GB' : 'N/A',
                ];
            })->all()
        );
    }
}
