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

use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Resource\Model\CloudProvider;

class CreateDnsZoneCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'dns:zone:create';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Create a new DNS zone')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the domain managed by the created DNS zone')
            ->addOption('provider', null, InputOption::VALUE_REQUIRED, 'The ID of the cloud provider where the DNS zone will be created');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $name = $this->input->getStringArgument('name');

        if (empty($name)) {
            $name = $this->output->ask('What is the name of the domain that the DNS zone will manage?');
        }

        $provider = $this->resolve(CloudProvider::class, 'Which cloud provider would you like to create the DNS zone on?');

        if (!$this->output->confirm('A DNS zone will cost $0.50/month if it isn\'t deleted in the next 12 hours. Would you like to proceed?')) {
            return;
        }

        $zone = $this->apiClient->createDnsZone($provider, $name);

        $nameServers = $this->wait(function () use ($zone) {
            return $this->apiClient->getDnsZone($zone->getId())->getNameServers();
        });

        if (!empty($nameServers)) {
            $this->output->horizontalTable(
                ['Domain Name', new TableSeparator(), 'Name Servers'],
                [[$zone->getName(), new TableSeparator(), implode(PHP_EOL, $nameServers)]]
            );
        }

        $this->output->info('DNS zone created');

        if ($this->output->confirm('Do you want to import the root DNS records for this domain?', false)) {
            $this->apiClient->importDnsRecords($zone);
        }

        if ($this->output->confirm('Do you want to import DNS records for subdomains of this domain?', false)) {
            $this->invoke(ImportDnsRecordsCommand::NAME, [
                'zone' => $zone->getName(),
            ]);
        }
    }
}
