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

namespace Ymir\Cli\Command\Cache;

use Ymir\Cli\Command\AbstractCommand;

class ListCachesCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'cache:list';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('List all the cache clusters that the current team has access to');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $this->output->table(
            ['Id', 'Name', 'Provider', 'Network', 'Region', 'Status', 'Engine', 'Type'],
            $this->apiClient->getCaches($this->cliConfiguration->getActiveTeamId())->map(function (array $cache) {
                return [
                    $cache['id'],
                    $cache['name'],
                    $cache['network']['provider']['name'],
                    $cache['network']['name'],
                    $cache['region'],
                    $this->output->formatStatus($cache['status']),
                    $cache['engine'],
                    $cache['type'],
                ];
            })->all()
        );
    }
}
