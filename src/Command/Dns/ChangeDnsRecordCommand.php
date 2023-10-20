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

class ChangeDnsRecordCommand extends AbstractDnsCommand
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
            ->addArgument('zone', InputArgument::REQUIRED, 'The name of the DNS zone that the DNS record belongs to')
            ->addArgument('type', InputArgument::REQUIRED, 'The DNS record type')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the DNS record without the domain')
            ->addArgument('value', InputArgument::REQUIRED, 'The value of the DNS record');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(Input $input, Output $output)
    {
        $this->apiClient->changeDnsRecord($input->getStringArgument('zone'), $input->getStringArgument('type'), $input->getStringArgument('name'), $input->getStringArgument('value'));

        $output->info('DNS record change applied');
    }
}
