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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Console\OutputInterface;
use Ymir\Cli\Support\Arr;

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
    protected function perform(InputInterface $input, OutputInterface $output)
    {
        $async = $this->getBooleanOption($input, 'async') || $this->getBooleanOption($input, 'yolo');
        $command = implode(' ', $this->getArrayArgument($input, 'wp-command'));
        $environment = (string) $this->getStringOption($input, 'environment');
        $exitCode = 0;

        if (empty($command) && $input->isInteractive()) {
            $command = $output->ask('Please enter the WP-CLI command to run');
        }

        if ('wp ' === substr($command, 0, 3)) {
            $command = substr($command, 3);
        }

        if (in_array($command, ['shell'])) {
            throw new RuntimeException(sprintf('The "wp %s" command isn\'t available remotely', $command));
        }

        $output->info(sprintf('Running "<comment>wp %s</comment>" %s "<comment>%s</comment>" environment', $command, $async ? 'asynchronously on' : 'on', $environment));

        $result = $this->invokeEnvironmentFunction($environment, [
            'php' => sprintf('bin/wp %s', $command),
        ], $async ? 0 : Arr::get($this->projectConfiguration->getEnvironment($environment), 'console.timeout', 60));

        if (!$async) {
            $output->newLine();
            $output->write("${result['output']}");

            $exitCode = $result['exitCode'];
        }

        return $exitCode;
    }
}
