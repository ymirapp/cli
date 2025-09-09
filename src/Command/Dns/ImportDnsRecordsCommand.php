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

class ImportDnsRecordsCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'dns:zone:import-records';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Import DNS records into a DNS zone')
            ->addArgument('zone', InputArgument::REQUIRED, 'The ID or name of the DNS zone that the DNS record belongs to')
            ->addArgument('subdomain', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'The subdomain(s) that we want to import');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $zone = $this->resolve(DnsZone::class, 'Which DNS zone would you like to import DNS records to?');
        $subdomains = $this->input->getArrayArgument('subdomain', false);

        if (empty($subdomains)) {
            $subdomains = array_filter(explode(',', (string) $this->output->ask('Please enter a comma-separated list of subdomains to import DNS records from? (Leave blank to import the root DNS records)')));
        }

        $this->apiClient->importDnsRecords($zone, $subdomains);

        $this->output->info('DNS records imported');
    }
}
