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

use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Exception\InvalidInputException;

class GetEnvironmentMetricsCommand extends AbstractProjectCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'environment:metrics';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Get cost and usage metrics of the environment')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to get the metrics of', 'staging')
            ->addOption('period', null, InputOption::VALUE_REQUIRED, 'The period to gather metrics for (1m, 5m, 30m, 1h, 8h, 1d, 3d, 7d, 1mo)', '1d');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(Input $input, Output $output)
    {
        $period = strtolower((string) $input->getStringOption('period'));

        if (!in_array($period, ['1m', '5m', '30m', '1h', '8h', '1d', '3d', '7d', '1mo'])) {
            throw new InvalidInputException('The given "period" is invalid. You may use: 1m, 5m, 30m, 1h, 8h, 1d, 3d, 7d, 1mo');
        }

        $environment = $input->getStringArgument('environment');
        $metrics = $this->apiClient->getEnvironmentMetrics($this->projectConfiguration->getProjectId(), $environment, $period);

        $output->newLine();
        $output->writeln(sprintf('  <info>Environment:</info> <comment>%s</comment>', $environment));

        $headers = [''];
        $row1 = [''];
        $row2 = [''];
        $row3 = ['Cost'];
        $total = 0;

        if (!empty($metrics['cdn'])) {
            $headers = array_merge($headers, [new TableSeparator(), 'Content Delivery Network', '']);
            $row1 = array_merge($row1, [new TableSeparator(), 'Bandwidth used', 'Requests']);
            $row2 = array_merge($row2, [new TableSeparator(), number_format((float) collect($metrics['cdn']['bandwidth'])->sum() / 1000000000, 2).'GB', number_format((float) collect($metrics['cdn']['requests'])->sum())]);
            $row3 = array_merge($row3, [new TableSeparator(), '$'.number_format($metrics['cdn']['cost_bandwidth'], 2), '$'.number_format($metrics['cdn']['cost_requests'], 2)]);
            $total += $metrics['cdn']['cost_bandwidth'] + $metrics['cdn']['cost_requests'];
        }
        if (!empty($metrics['gateway'])) {
            $headers = array_merge($headers, [new TableSeparator(), 'API Gateway']);
            $row1 = array_merge($row1, [new TableSeparator(), 'Requests']);
            $row2 = array_merge($row2, [new TableSeparator(), number_format((float) collect($metrics['gateway']['requests'])->sum())]);
            $row3 = array_merge($row3, [new TableSeparator(), '$'.number_format($metrics['gateway']['cost_requests'], 2)]);
            $total += $metrics['gateway']['cost_requests'];
        }
        if (!empty($metrics['website'])) {
            $headers = array_merge($headers, [new TableSeparator(), 'Website Lambda function', '', '']);
            $row1 = array_merge($row1, [new TableSeparator(), 'Invocations', 'Duration', 'Avg duration']);
            $row2 = array_merge($row2, [new TableSeparator(), number_format((float) collect($metrics['website']['invocations'])->sum()), number_format((float) collect($metrics['website']['duration'])->sum() / 1000).'s', number_format((float) collect($metrics['website']['avg_duration'])->avg()).'ms']);
            $row3 = array_merge($row3, [new TableSeparator(), '$'.number_format($metrics['website']['cost_invocations'], 2), '$'.number_format($metrics['website']['cost_duration'], 2), '-']);
            $total += $metrics['website']['cost_duration'] + $metrics['website']['cost_invocations'];
        }
        if (!empty($metrics['console'])) {
            $headers = array_merge($headers, [new TableSeparator(), 'Console Lambda function', '', '']);
            $row1 = array_merge($row1, [new TableSeparator(), 'Invocations', 'Duration', 'Avg duration']);
            $row2 = array_merge($row2, [new TableSeparator(), number_format((float) collect($metrics['console']['invocations'])->sum()), number_format((float) collect($metrics['console']['duration'])->sum() / 1000).'s', number_format((float) collect($metrics['console']['avg_duration'])->avg()).'ms']);
            $row3 = array_merge($row3, [new TableSeparator(), '$'.number_format($metrics['console']['cost_invocations'], 2), '$'.number_format($metrics['console']['cost_duration'], 2), '-']);
            $total += $metrics['console']['cost_duration'] + $metrics['console']['cost_invocations'];
        }

        $headers = array_merge($headers, [new TableSeparator(), '<comment>Total</comment>']);
        $row1 = array_merge($row1, [new TableSeparator(), '']);
        $row2 = array_merge($row2, [new TableSeparator(), '']);
        $row3 = array_merge($row3, [new TableSeparator(), '$'.number_format($total, 2)]);

        $output->horizontalTable($headers, [$row1, $row2, $row3]);

        $output->note('This is a partial cost estimate.');
    }
}
