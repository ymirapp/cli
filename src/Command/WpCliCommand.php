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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;

class WpCliCommand extends AbstractInvocationCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'wp';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Execute a WP-CLI command')
            ->addArgument('wp-command', InputArgument::IS_ARRAY, 'The WP-CLI command to execute')
            ->addOption('environment', null, InputOption::VALUE_REQUIRED, 'The environment name', 'staging')
            ->addOption('async', null, InputOption::VALUE_NONE, 'Execute WP-CLI command asynchronously')
            ->addHiddenOption('yolo', null, InputOption::VALUE_NONE);
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(Input $input, Output $output)
    {
        $async = $input->getBooleanOption('async') || $input->getBooleanOption('yolo');
        $command = implode(' ', $input->getArrayArgument('wp-command'));
        $environment = (string) $input->getStringOption('environment');
        $exitCode = Command::SUCCESS;

        if (empty($command)) {
            $command = $output->ask('Please enter the WP-CLI command to run');
        }

        if (str_starts_with($command, 'wp ')) {
            $command = substr($command, 3);
        }

        if (in_array($command, ['shell'])) {
            throw new RuntimeException(sprintf('The "wp %s" command isn\'t available remotely', $command));
        } elseif (in_array($command, ['db import', 'db export'])) {
            throw new RuntimeException(sprintf('Please use the "ymir database:%s" command instead of the "wp %s" command', substr($command, 3), $command));
        }

        $output->info(sprintf('Running "<comment>wp %s</comment>" %s "<comment>%s</comment>" environment', $command, $async ? 'asynchronously on' : 'on', $environment));

        $result = $this->invokeWpCliCommand($command, $environment, $async ? 0 : null);

        if (!$async) {
            $output->newLine();
            $output->write("{$result['output']}");

            $exitCode = $result['exitCode'];
        }

        return $exitCode;
    }
}
