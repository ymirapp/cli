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

class ChangeDnsRecordCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'dns:record:change';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Change the value of a DNS record (Will overwrite existing DNS record if it already exists)')
            ->addArgument('zone', InputArgument::REQUIRED, 'The ID or name of the DNS zone that the DNS record belongs to')
            ->addArgument('type', InputArgument::REQUIRED, 'The DNS record type')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the DNS record without the domain')
            ->addArgument('value', InputArgument::REQUIRED, 'The value of the DNS record');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $zone = $this->resolve(DnsZone::class, 'Which DNS zone would you like to change a DNS record in?');

        $this->apiClient->changeDnsRecord($zone, $this->input->getStringArgument('type'), $this->input->getStringArgument('name'), $this->input->getStringArgument('value'));

        $this->output->info('DNS record change applied');
    }
}
