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
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\OutputStyle;

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
            ->addArgument('zone', InputArgument::REQUIRED, 'The name of the DNS zone that the DNS record belongs to')
            ->addArgument('type', InputArgument::REQUIRED, 'The DNS record type')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the DNS record without the domain')
            ->addArgument('value', InputArgument::REQUIRED, 'The value of the DNS record')
            ->setDescription('Change the value of a DNS record (Will overwrite existing DNS record if it already exists)');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $this->apiClient->changeDnsRecord($this->getStringArgument($input, 'zone'), $this->getStringArgument($input, 'type'), $this->getStringArgument($input, 'name'), $this->getStringArgument($input, 'value'));

        $output->info('DNS record change applied');
    }
}
