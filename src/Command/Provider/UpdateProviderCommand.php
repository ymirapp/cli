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

namespace Ymir\Cli\Command\Provider;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Console\ConsoleOutput;

class UpdateProviderCommand extends AbstractProviderCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'provider:update';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Update a cloud provider')
            ->addArgument('provider', InputArgument::REQUIRED, 'The ID of the cloud provider to update');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, ConsoleOutput $output)
    {
        $provider = $this->apiClient->getProvider($this->getNumericArgument($input, 'provider'));

        $name = (string) $output->ask('Please enter a name for the cloud provider connection', $provider->get('name'));

        $this->apiClient->updateProvider($provider->get('id'), $this->getAwsCredentials($output), $name);

        $output->info('Cloud provider updated');
    }
}
