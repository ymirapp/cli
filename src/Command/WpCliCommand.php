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
use Ymir\Cli\Console\OutputStyle;

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
            ->addArgument('wp-command', InputArgument::OPTIONAL, 'The WP-CLI command to execute')
            ->addOption('environment', null, InputOption::VALUE_REQUIRED, 'The environment name', 'staging');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $command = $this->getStringArgument($input, 'wp-command');
        $environment = (string) $this->getStringOption($input, 'environment');

        if (empty($command) && $input->isInteractive()) {
            $command = $output->ask('Please enter the WP-CLI command to run');
        }

        if ('wp ' === substr($command, 0, 3)) {
            $command = substr($command, 3);
        }

        if (in_array($command, ['shell'])) {
            throw new RuntimeException(sprintf('The "wp %s" command isn\'t available remotely', $command));
        }

        $output->info(sprintf('Running "<comment>wp %s</comment>" on "<comment>%s</comment>" environment', $command, $environment));

        $result = $this->invokeEnvironmentFunction($environment, [
            'php' => sprintf('bin/wp %s', $command),
        ]);

        $output->newLine();
        $output->write("${result['output']}");

        return $result['exitCode'];
    }
}
