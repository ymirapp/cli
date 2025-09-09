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

namespace Ymir\Cli\Command\Dns;

use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Resource\Model\DnsZone;

class ListDnsZonesCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'dns:zone:list';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('List the DNS zones that belong to the currently active team');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $this->output->table(
            ['Id', 'Provider', 'Domain Name', 'Name Servers'],
            $this->apiClient->getDnsZones($this->getTeam())->map(function (DnsZone $zone) {
                return [$zone->getId(), $zone->getProvider()->getName(), $zone->getName(), implode(PHP_EOL, $zone->getNameServers())];
            })->all()
        );
    }
}
