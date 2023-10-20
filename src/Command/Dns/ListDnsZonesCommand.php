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
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;

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
    protected function perform(Input $input, Output $output)
    {
        $output->table(
            ['Id', 'Provider', 'Domain Name', 'Name Servers'],
            $this->apiClient->getDnsZones($this->cliConfiguration->getActiveTeamId())->map(function (array $zone) {
                return [$zone['id'], $zone['provider']['name'], $zone['domain_name'], implode(PHP_EOL, $zone['name_servers'])];
            })->all()
        );
    }
}
