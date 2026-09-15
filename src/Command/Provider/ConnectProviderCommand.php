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
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Resource\Model\CloudProvider;

class ConnectProviderCommand extends AbstractCommand
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
    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Connect a cloud provider to the currently active team')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the cloud provider connection')
            ->addOption('aws-profile', null, InputOption::VALUE_REQUIRED, 'The AWS credential profile used for access key authentication');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(): void
    {
        $this->provision(CloudProvider::class);

        $this->output->info('Cloud provider connected');
    }
}
