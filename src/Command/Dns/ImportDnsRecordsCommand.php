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
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;

class ImportDnsRecordsCommand extends AbstractDnsCommand
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
            ->addArgument('zone', InputArgument::REQUIRED, 'The name of the DNS zone that the DNS record belongs to')
            ->addArgument('subdomain', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'The subdomain(s) that we want to import');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(Input $input, Output $output)
    {
        $subdomains = $input->getArrayArgument('subdomain', false);

        if (empty($subdomains)) {
            $subdomains = explode(',', (string) $output->ask('Please enter a comma-separated list of subdomains to import DNS records from (leave blank to import the root DNS records)'));
        }

        $this->apiClient->importDnsRecords($input->getStringArgument('zone'), $subdomains);

        $output->info('DNS records imported');
    }
}
