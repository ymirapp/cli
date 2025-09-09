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
use Ymir\Cli\Exception\Resource\ResourceStateException;
use Ymir\Cli\Resource\Model\Network;

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
        $network = $this->resolve(Network::class, 'Which network would you like to remove the NAT gateway from?');

        if (!$network->hasNatGateway()) {
            throw new ResourceStateException(sprintf('The "%s" network doesn\'t have a NAT gateway', $network->getName()));
        }

        $this->apiClient->removeNatGateway($network);

        $this->output->infoWithDelayWarning(sprintf('NAT gateway removed from the "%s" network', $network->getName()));
    }
}
