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

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Exception\InvalidInputException;

class QueryEnvironmentLogsCommand extends AbstractEnvironmentLogsCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'environment:logs:query';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Retrieve logs for an environment function')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to get the logs of', 'staging')
            ->addArgument('function', InputArgument::OPTIONAL, 'The environment function to get the logs of', 'website')
            ->addOption('lines', null, InputOption::VALUE_REQUIRED, 'The number of log lines to display', 10)
            ->addOption('order', null, InputOption::VALUE_REQUIRED, 'The order to display the logs in', 'asc')
            ->addOption('period', null, InputOption::VALUE_REQUIRED, 'The period of time to get the logs for', '1h')
            ->addOption('timezone', null, InputOption::VALUE_REQUIRED, 'The timezone to display the log times in');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $environment = $this->input->getStringArgument('environment');
        $function = strtolower($this->input->getStringArgument('function'));
        $lines = (int) $this->input->getNumericOption('lines');
        $order = strtolower($this->input->getStringOption('order'));

        if ($lines < 1) {
            throw new InvalidInputException('The number of lines must be at least 1');
        } elseif (!in_array($order, ['asc', 'desc'])) {
            throw new InvalidInputException('The order must be either "asc" or "desc"');
        }

        $logs = $this->apiClient->getEnvironmentLogs($this->projectConfiguration->getProjectId(), $environment, $function, Carbon::now()->sub(CarbonInterval::fromString($this->input->getStringOption('period')))->getTimestampMs(), 'desc');

        if ($logs->isEmpty()) {
            $this->output->info('No logs found for the given period');

            return;
        }

        $logs = $logs->take($lines);

        if ('asc' === $order) {
            $logs = $logs->reverse();
        }

        $this->writeLogs($logs, $this->input->getStringOption('timezone'));
    }
}
