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

namespace Ymir\Cli\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Console\ConsoleOutput;

class ShellCommand extends AbstractInvocationCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'shell';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Opens an interactive console for running commands on the cloud provider')
            ->addOption('environment', null, InputOption::VALUE_REQUIRED, 'The environment name', 'staging');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, ConsoleOutput $output)
    {
        $environment = (string) $this->getStringOption($input, 'environment');
    }
}
