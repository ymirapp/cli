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
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Resource\ResourceNotFoundException;
use Ymir\Cli\Exception\Resource\ResourceStateException;
use Ymir\Cli\Resource\Model\DnsRecord;
use Ymir\Cli\Resource\Model\DnsZone;

class DeleteDnsRecordCommand extends AbstractCommand
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
    protected function perform()
    {
        $zone = $this->resolve(DnsZone::class, 'Which DNS zone would you like to delete DNS record(s) from?');

        if (!$this->output->confirm('Are you sure you want to delete these DNS record(s)?', false)) {
            return;
        }

        $name = $this->input->getStringOption('name');
        $recordId = $this->input->getNumericArgument('record');
        $type = $this->input->getStringOption('type');
        $value = $this->input->getStringOption('value');

        if (empty($name) && empty($recordId) && empty($type) && empty($value) && !$this->output->warningConfirmation('You are about to delete all DNS records')) {
            return;
        }

        $recordsToDelete = $this->apiClient->getDnsRecords($zone)->filter(function (DnsRecord $record) use ($recordId): bool {
            return empty($recordId) || $record->getId() === $recordId;
        });

        if (!empty($recordId) && 1 !== $recordsToDelete->count()) {
            throw new ResourceNotFoundException('DNS record', $recordId);
        }

        $recordsToDelete = $recordsToDelete->filter(function (DnsRecord $record): bool {
            return !$record->isInternal();
        });

        if (!empty($recordId) && $recordsToDelete->isEmpty()) {
            throw new ResourceStateException(sprintf('DNS record "%d" is internal and cannot be deleted', $recordId));
        }

        $recordsToDelete = $recordsToDelete->filter(function (DnsRecord $record) use ($name, $zone): bool {
            return empty($name) || $record->getName() === $name || $record->getName() === sprintf('%s.%s', $name, $zone->getName());
        })->filter(function (DnsRecord $record) use ($type): bool {
            return empty($type) || $record->getType() === strtoupper($type);
        })->filter(function (DnsRecord $record) use ($value): bool {
            return empty($value) || $record->getValue() === $value;
        });

        if ($recordsToDelete->isEmpty()) {
            throw new InvalidInputException('No DNS records matched the given filters');
        }

        $recordsToDelete->each(function (DnsRecord $record) use ($zone): void {
            $this->apiClient->deleteDnsRecord($zone, $record);
        });

        $this->output->info('DNS record(s) deleted');
    }
}
