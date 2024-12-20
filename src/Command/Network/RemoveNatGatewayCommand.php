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

namespace Ymir\Cli\Command\Network;

use Symfony\Component\Console\Input\InputArgument;
use Ymir\Cli\Command\AbstractCommand;

class RemoveNatGatewayCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'network:nat:remove';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Remove a NAT gateway from a network\'s private subnet')
            ->addArgument('network', InputArgument::OPTIONAL, 'The ID or name of the network to remove the NAT gateway from');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $this->apiClient->removeNatGateway($this->determineNetwork('Which network would like to remove the NAT gateway from'));

        $this->output->infoWithDelayWarning('NAT gateway removed');
    }
}
