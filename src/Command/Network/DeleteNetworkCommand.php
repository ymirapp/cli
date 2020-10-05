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

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\ConsoleOutput;

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
            ->setDescription('Delete an existing network')
            ->addArgument('network', InputArgument::OPTIONAL, 'The ID or name of the network to delete');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, ConsoleOutput $output)
    {
        $networkIdOrName = $this->getStringArgument($input, 'network');
        $networks = $this->apiClient->getTeamNetworks($this->cliConfiguration->getActiveTeamId());

        if (empty($networkIdOrName) && $input->isInteractive()) {
            $networkIdOrName = $output->choiceWithResourceDetails('Which network like to delete', $networks);
        }

        $network = $networks->firstWhere('name', $networkIdOrName) ?? $networks->firstWhere('name', $networkIdOrName);

        if (1 < $networks->where('name', $networkIdOrName)->count()) {
            throw new RuntimeException(sprintf('Unable to select a network because more than one network has the name "%s"', $networkIdOrName));
        } elseif (empty($network['id'])) {
            throw new RuntimeException(sprintf('Unable to find a network with "%s" as the ID or name', $networkIdOrName));
        } elseif (!$output->confirm('Are you sure you want to delete this network?', false)) {
            return;
        }

        $this->apiClient->deleteNetwork((int) $network['id']);

        $output->infoWithDelayWarning('Network deleted');
    }
}
