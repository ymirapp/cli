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
        $this->apiClient->addNatGateway($this->determineNetwork('Which network would like to add a NAT gateway to'));

        $this->output->infoWithDelayWarning('NAT gateway added');
    }
}
