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

class DeleteDnsZoneCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'dns:zone:delete';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->addArgument('zone', InputArgument::REQUIRED, 'The ID or name of the DNS zone to delete')
            ->setDescription('Delete an existing DNS zone');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $zone = $this->apiClient->getDnsZone($this->getStringArgument($input, 'zone'));

        if ($input->isInteractive() && !$output->confirm('Are you sure you want to delete this DNS zone?', false)) {
            return;
        }

        $this->apiClient->deleteDnsZone((int) $zone['id']);

        $output->info('DNS zone deleted');
    }
}
