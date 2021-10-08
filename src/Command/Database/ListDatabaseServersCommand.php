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

use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\ConsoleOutput;

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
    protected function perform(InputInterface $input, ConsoleOutput $output)
    {
        $output->table(
            ['Id', 'Name', 'Provider', 'Network', 'Region', 'Status', 'Locked', 'Type', 'Storage'],
            $this->apiClient->getTeamDatabaseServers($this->cliConfiguration->getActiveTeamId())->map(function (array $database) use ($output) {
                return [
                    $database['id'],
                    $database['name'],
                    $database['network']['provider']['name'],
                    $database['network']['name'],
                    $database['region'],
                    $output->formatStatus($database['status']),
                    $output->formatBoolean($database['locked']),
                    $database['type'],
                    $database['storage'].'GB',
                ];
            })->all()
        );
    }
}
