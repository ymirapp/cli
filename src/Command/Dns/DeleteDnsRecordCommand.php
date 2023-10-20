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
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;

class DeleteDnsRecordCommand extends AbstractDnsCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'dns:record:delete';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Delete DNS record(s) from a DNS zone')
            ->addArgument('zone', InputArgument::REQUIRED, 'The ID or name of the DNS zone that the DNS record belongs to')
            ->addArgument('record', InputArgument::OPTIONAL, 'The ID of the DNS record to delete')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'The name of the DNS record without the domain')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'The DNS record type')
            ->addOption('value', null, InputOption::VALUE_REQUIRED, 'The value of the DNS record');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(Input $input, Output $output)
    {
        $zoneIdOrName = $input->getStringArgument('zone');

        if (!$output->confirm('Are you sure you want to delete these DNS record(s)?', false)) {
            return;
        }

        $name = $input->getStringOption('name');
        $recordId = $input->getNumericArgument('record');
        $type = $input->getStringOption('type');
        $value = $input->getStringOption('value');
        $zone = $this->apiClient->getDnsZone($zoneIdOrName);

        if (empty($name) && empty($recordId) && empty($type) && empty($value) && !$output->confirm('You are about to delete all DNS records. Do you want to proceed?', false)) {
            return;
        }

        $records = !empty($recordId) ? [['id' => $recordId]] : $this->apiClient->getDnsRecords($zone['id'])->filter(function (array $record) {
            return !$record['internal'];
        })->filter(function (array $record) use ($name, $zone) {
            return empty($name) || $record['name'] === $name || $record['name'] === sprintf('%s.%s', $name, $zone['domain_name']);
        })->filter(function (array $record) use ($type) {
            return empty($type) || $record['type'] === strtoupper($type);
        })->filter(function (array $record) use ($value) {
            return empty($value) || $record['value'] === $value;
        });

        foreach ($records as $record) {
            $this->apiClient->deleteDnsRecord($zone['id'], $record['id']);
        }

        $output->info('DNS record(s) deleted');
    }
}
