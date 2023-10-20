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

use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;

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
    protected function perform(Input $input, Output $output)
    {
        $name = $output->ask('Please enter a name for the cloud provider connection', 'AWS');

        $credentials = $this->getAwsCredentials($output);

        $this->apiClient->createProvider($this->cliConfiguration->getActiveTeamId(), $name, $credentials);

        $output->info('Cloud provider connected');
    }
}
