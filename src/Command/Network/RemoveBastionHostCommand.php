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
use Ymir\Cli\Resource\Model\BastionHost;
use Ymir\Cli\Resource\Model\Network;

class RemoveBastionHostCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'network:bastion:remove';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Remove bastion host from a network')
            ->addArgument('network', InputArgument::OPTIONAL, 'The ID or name of the network to remove the bastion host from');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $network = $this->resolve(Network::class, 'Which network would you like to remove the bastion host from?');

        if (!$network->getBastionHost() instanceof BastionHost) {
            throw new ResourceStateException(sprintf('The "%s" network doesn\'t have a bastion host', $network->getName()));
        }

        $this->apiClient->removeBastionHost($network);

        $this->output->infoWithDelayWarning(sprintf('Bastion host removed from the "%s" network', $network->getName()));
    }
}
