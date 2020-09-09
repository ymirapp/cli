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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\OutputStyle;

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
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the domain managed by the created DNS zone')
            ->addOption('provider', null, InputOption::VALUE_REQUIRED, 'The cloud provider region where the DNS zone will created')
            ->setDescription('Create a new DNS zone');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $providerId = $this->determineCloudProvider($input, $output, 'Enter the ID of the cloud provider where the DNS zone will be created');

        if (!$output->confirm('A DNS Zone will cost $0.50/month if it isn\'t deleted in the next 12 hours. Would you like to proceed?', true)) {
            return;
        }

        $zone = $this->apiClient->createDnsZone($providerId, $this->getStringArgument($input, 'name'));

        $nameServers = $this->wait(function () use ($zone) {
            return $this->apiClient->getDnsZone($zone['id'])['name_servers'] ?? [];
        });

        if (!empty($nameServers)) {
            $output->horizontalTable(
                ['Domain Name', new TableSeparator(), 'Name Servers'],
                [[$zone['name'], new TableSeparator(), implode(PHP_EOL, $nameServers)]]
            );
        }

        $output->info('DNS zone created');
    }
}
