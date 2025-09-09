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
use Ymir\Cli\Resource\Model\DnsRecord;
use Ymir\Cli\Resource\Model\DnsZone;

class ListDnsRecordsCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'dns:record:list';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('List the DNS records belonging to a DNS zone')
            ->addArgument('zone', InputArgument::OPTIONAL, 'The ID or name of the DNS zone to list DNS records from');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $zone = $this->resolve(DnsZone::class, 'Which DNS zone would you like to list DNS records from?');

        $this->output->table(
            ['Id', 'Domain Name', 'Type', 'Value', 'Internal'],
            $this->apiClient->getDnsRecords($zone)->map(function (DnsRecord $record) {
                return [$record->getId(), $record->getName(), $record->getType(), str_replace(',', "\n", $record->getValue()), $this->output->formatBoolean($record->isInternal())];
            })->all()
        );
    }
}
