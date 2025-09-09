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

class AddNatGatewayCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'network:nat:add';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Add a NAT gateway to a network\'s private subnet')
            ->addArgument('network', InputArgument::OPTIONAL, 'The ID or name of the network to add a NAT gateway to');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $network = $this->resolve(Network::class, 'Which network would you like to add a NAT gateway to?');

        if ($network->hasNatGateway()) {
            throw new ResourceStateException(sprintf('The "%s" network already has a NAT gateway', $network->getName()));
        }

        $this->apiClient->addNatGateway($network);

        $this->output->infoWithDelayWarning(sprintf('NAT gateway added to the "%s" network', $network->getName()));
    }
}
