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

namespace Ymir\Cli\Command\Environment;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Exception\InvalidInputException;

class WatchEnvironmentLogsCommand extends AbstractEnvironmentLogsCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'environment:logs:watch';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Continuously monitor and display the most recent logs for an environment function')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to get the logs of', 'staging')
            ->addArgument('function', InputArgument::OPTIONAL, 'The environment function to get the logs of', 'website')
            ->addOption('interval', null, InputOption::VALUE_REQUIRED, 'Interval (in seconds) to poll for new logs', 30)
            ->addOption('timezone', null, InputOption::VALUE_REQUIRED, 'The timezone to display the log times in');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(Input $input, Output $output)
    {
        $environment = $input->getStringArgument('environment');
        $function = strtolower($input->getStringArgument('function'));
        $interval = (int) $input->getNumericOption('interval');
        $since = (int) round(microtime(true) * 1000);

        if ($interval < 20) {
            throw new InvalidInputException('Polling interval must be at least 20 seconds');
        }

        while (true) {
            sleep($interval);

            $this->writeLogs($this->apiClient->getEnvironmentLogs($this->projectConfiguration->getProjectId(), $environment, $function, $since), $output, $input->getStringOption('timezone'));

            $since += $interval * 1000 + 1;
        }
    }
}
