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

use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Console\OutputInterface;

class ConnectProviderCommand extends AbstractProviderCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'provider:connect';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Connect a cloud provider to the currently active team');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputInterface $output)
    {
        $name = $output->ask('Please enter a name for the cloud provider connection', 'AWS');

        $credentials = $this->getAwsCredentials($output);

        $this->apiClient->createProvider($name, $credentials, $this->cliConfiguration->getActiveTeamId());

        $output->info('Cloud provider connected');
    }
}
