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
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Console\ConsoleOutput;

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
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'The name of the DNS record without the domain')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'The DNS record type')
            ->addOption('value', null, InputOption::VALUE_REQUIRED, 'The value of the DNS record');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, ConsoleOutput $output)
    {
        $zoneIdOrName = $this->getStringArgument($input, 'zone');

        if (!$output->confirm('Are you sure you want to delete these DNS records?', false)) {
            return;
        }

        $zone = $this->apiClient->getDnsZone($zoneIdOrName);

        foreach ($this->getDnsRecords($input, $zone) as $record) {
            $this->apiClient->deleteDnsRecord((int) $zone['id'], (int) $record['id']);
        }

        $output->info('DNS records deleted');
    }

    /**
     * Get the DNS records of the DNS zone filtered based on the console input.
     */
    private function getDnsRecords(InputInterface $input, array $zone): array
    {
        return $this->apiClient->getDnsRecords($zone['id'])->filter(function (array $record) use ($input, $zone) {
            $name = $this->getStringOption($input, 'name');

            return empty($name) || $record['name'] === $name || $record['name'] === sprintf('%s.%s', $name, $zone['name']);
        })->filter(function (array $record) use ($input) {
            $type = $this->getStringOption($input, 'type');

            return empty($type) || $record['type'] === strtoupper($type);
        })->filter(function (array $record) use ($input) {
            $value = $this->getStringOption($input, 'value');

            return empty($value) || $record['value'] === $value;
        })->values()->all();
    }
}
