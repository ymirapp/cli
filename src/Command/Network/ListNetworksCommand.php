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
use Ymir\Cli\Resource\Model\Network;

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
        $this->output->table(
            ['Id', 'Name', 'Provider', 'Region', 'Status', 'NAT Gateway'],
            $this->apiClient->getNetworks($this->getTeam())->map(function (Network $network) {
                return [
                    $network->getId(),
                    $network->getName(),
                    $network->getProvider()->getName(),
                    $network->getRegion(),
                    $this->output->formatStatus($network->getStatus()),
                    $this->output->formatBoolean($network->hasNatGateway()),
                ];
            })->all()
        );
    }
}
