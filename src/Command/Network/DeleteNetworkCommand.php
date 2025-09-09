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
use Ymir\Cli\Resource\Model\Network;

class DeleteNetworkCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'network:delete';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Delete a network')
            ->addArgument('network', InputArgument::OPTIONAL, 'The ID or name of the network to delete');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $network = $this->resolve(Network::class, 'Which network would you like to delete?');

        if (!$this->output->confirm(sprintf('Are you sure you want to delete the "%s" network?', $network->getName()), false)) {
            return;
        }

        $this->apiClient->deleteNetwork($network);

        $this->output->infoWithDelayWarning('Network deleted');
    }
}
