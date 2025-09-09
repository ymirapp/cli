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
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Resource\Model\DnsZone;

class DeleteDnsZoneCommand extends AbstractCommand
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
        $zone = $this->resolve(DnsZone::class, 'Which DNS zone would you like to delete?');

        if (!$this->output->confirm(sprintf('Are you sure you want to delete the "<comment>%s</comment>" DNS zone?', $zone->getName()), false)) {
            return;
        }

        $this->apiClient->deleteDnsZone($zone);

        $this->output->info('DNS zone deleted');
    }
}
