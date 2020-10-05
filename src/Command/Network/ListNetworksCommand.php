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

use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\ConsoleOutput;

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
    protected function perform(InputInterface $input, ConsoleOutput $output)
    {
        $networks = $this->apiClient->getTeamNetworks($this->cliConfiguration->getActiveTeamId());

        $output->table(
            ['Id', 'Name', 'Provider', 'Region', 'Status'],
            $networks->map(function (array $network) use ($output) {
                return [$network['id'], $network['name'], $network['provider']['name'], $network['region'], $output->formatStatus($network['status'])];
            })->all()
        );
    }
}
