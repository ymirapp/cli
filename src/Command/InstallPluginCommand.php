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

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Process\Process;
use Ymir\Cli\Console\OutputStyle;

class InstallPluginCommand extends AbstractProjectCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'install-plugin';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Installs the Ymir WordPress plugin');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $command = null;
        $message = 'Installing Ymir plugin ';
        $projectType = $this->projectConfiguration->getProjectType();

        if ('bedrock' === $projectType) {
            $command = 'composer require ymirapp/wordpress-plugin';
            $message .= 'using Composer';
        } elseif ('wordpress' === $projectType) {
            $command = 'cp -R /Users/carlalexander/Projects/ymir/wordpress-plugin wp-content/plugins/ymir';
            $message .= 'manually';
        }

        if (!is_string($command)) {
            throw new RuntimeException('Unable to install Ymir plugin');
        }

        $output->info($message);
        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException($process->getErrorOutput());
        }
    }
}
