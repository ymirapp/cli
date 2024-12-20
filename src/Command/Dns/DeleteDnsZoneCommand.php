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

use Symfony\Component\Console\Input\InputArgument;

class DeleteDnsZoneCommand extends AbstractDnsCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'dns:zone:delete';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Delete a DNS zone')
            ->addArgument('zone', InputArgument::OPTIONAL, 'The ID or name of the DNS zone to delete');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $zone = $this->determineDnsZone('Which DNS zone would you like to delete');

        if (!$this->output->confirm('Are you sure you want to delete this DNS zone?', false)) {
            return;
        }

        $this->apiClient->deleteDnsZone((int) $zone['id']);

        $this->output->info('DNS zone deleted');
    }
}
