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

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Console\OutputStyle;

class GetEnvironmentInfoCommand extends AbstractProjectCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'environment:info';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Get information on the project environment(s)')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The environment name');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $environments = $input->getArgument('environment');

        if (!is_array($environments)) {
            $environments = (array) $environments;
        }

        if (empty($environments)) {
            $output->info('Listing information on all environments found in <comment>ymir.yml</comment> file');
            $environments = $this->projectConfiguration->getEnvironments();
        }

        foreach ($environments as $environment) {
            $this->displayEnvironmentTable($output, $environment);
        }
    }

    /**
     * Display the table with the environment information.
     */
    private function displayEnvironmentTable(OutputStyle $output, string $environment)
    {
        $database = $this->getEnvironmentDatabase($environment);
        $environment = $this->apiClient->getEnvironment($this->projectConfiguration->getProjectId(), $environment);
        $headers = ['Name', 'Domain', 'API', 'CDN'];
        $row = $environment->only(['name', 'vanity_domain_name'])->values()->all();

        $row[] = $environment['api']['domain_name'] ?? '<fg=red>Unavailable</>';

        if (empty($environment['content_delivery_network'])) {
            $row[] = '<fg=red>Unavailable</>';
        } elseif (!empty($environment['content_delivery_network']['domain_name'])) {
            $row[] = $environment['content_delivery_network']['domain_name'];
        } elseif (!empty($environment['content_delivery_network']['status'])) {
            $row[] = sprintf('<comment>%s</comment>', ucfirst($environment['content_delivery_network']['status']));
        }

        if (is_string($database)) {
            $headers[] = 'Database';
            $row[] = $database;
        }

        $output->horizontalTable($headers, [$row]);
    }

    /**
     * Get the information on the given environment's database.
     */
    private function getEnvironmentDatabase(string $environment): ?string
    {
        $environment = $this->projectConfiguration->getEnvironment($environment);

        if (empty($environment['database'])) {
            return null;
        }

        $databaseName = $environment['database']['host'] ?? $environment['database'];

        if (!is_string($databaseName)) {
            return null;
        }

        $database = $this->apiClient->getDatabaseServers($this->cliConfiguration->getActiveTeamId())->firstWhere('name', $databaseName);

        if (!is_array($database)) {
            throw new RuntimeException(sprintf('There is no "%s" database on your current team', $databaseName));
        }

        if (!empty($database['status']) && 'available' !== $database['status']) {
            return sprintf('<comment>%s</comment>', ucfirst($database['status']));
        }

        return $database['endpoint'] ?? '<fg=red>Unavailable</>';
    }
}
