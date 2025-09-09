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
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Connect a cloud provider to the currently active team');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $this->provision(CloudProvider::class);

        $this->output->info('Cloud provider connected');
    }
}
