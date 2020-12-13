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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\ConsoleOutput;

class CreateNetworkCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'network:create';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Create a new network')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the network')
            ->addOption('provider', null, InputOption::VALUE_REQUIRED, 'The cloud provider region where the network will created')
            ->addOption('region', null, InputOption::VALUE_REQUIRED, 'The cloud provider region where the network will be located');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, ConsoleOutput $output)
    {
        $name = $this->getStringArgument($input, 'name');

        if (empty($name) && $input->isInteractive()) {
            $name = $output->ask('What is the name of the network being created');
        }

        $providerId = $this->determineCloudProvider('Enter the ID of the cloud provider where the DNS zone will be created', $input, $output);

        $this->apiClient->createNetwork($providerId, $name, $this->determineRegion('Enter the name of the region where the network will be created', $providerId, $input, $output));

        $output->infoWithDelayWarning('Network created');
    }
}
