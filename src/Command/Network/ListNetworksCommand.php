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

namespace Ymir\Cli\Command\Network;

use Ymir\Cli\Command\AbstractCommand;

class ListNetworksCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'network:list';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('List the networks that belong to the currently active team');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $networks = $this->apiClient->getNetworks($this->cliConfiguration->getActiveTeamId());

        $this->output->table(
            ['Id', 'Name', 'Provider', 'Region', 'Status', 'NAT Gateway'],
            $networks->map(function (array $network) {
                return [$network['id'], $network['name'], $network['provider']['name'], $network['region'], $this->output->formatStatus($network['status']), $this->output->formatBoolean($network['has_nat_gateway'])];
            })->all()
        );
    }
}
