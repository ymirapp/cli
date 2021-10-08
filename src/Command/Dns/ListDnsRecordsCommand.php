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
use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Console\ConsoleOutput;

class ListDnsRecordsCommand extends AbstractDnsCommand
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
    protected function perform(InputInterface $input, ConsoleOutput $output)
    {
        $zone = $this->determineDnsZone('Which DNS zone would you like to list DNS records from', $input, $output);

        $output->table(
            ['Id', 'Domain Name', 'Type', 'Value', 'Internal'],
            $this->apiClient->getDnsRecords($zone['id'])->map(function (array $record) use ($output) {
                return [$record['id'], $record['name'], $record['type'], str_replace(',', "\n", $record['value']), $output->formatBoolean($record['internal'])];
            })->all()
        );
    }
}
