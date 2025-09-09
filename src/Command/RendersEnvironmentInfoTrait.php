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

use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\ResourceCollection;

/**
 * Trait for commands that render environment information.
 */
trait RendersEnvironmentInfoTrait
{
    /**
     * The database servers that belong to the active team.
     *
     * @var ResourceCollection|null
     */
    private $databaseServers;

    /**
     * Display the table with the environment information.
     */
    protected function displayEnvironmentTable(Environment $environment)
    {
        $databaseServer = $this->getEnvironmentDatabaseServer($environment->getName());

        $headers = ['Name', 'Domain'];
        $row = [$environment->getName(), $environment->getVanityDomainName()];

        $gateway = $environment->getGateway();
        if (!empty($gateway)) {
            $headers[] = strtoupper($gateway['type']).' Gateway';
            $row[] = $gateway['domain_name'] ?? '<fg=red>Unavailable</>';
        } else {
            $headers[] = 'Gateway';
            $row[] = '<fg=red>Disabled</>';
        }

        $headers = array_merge($headers, ['CDN', 'Public assets']);

        $cdn = $environment->getContentDeliveryNetwork();
        if (empty($cdn)) {
            $row[] = '<fg=red>Unavailable</>';
        } elseif (!empty($cdn['domain_name'])) {
            $row[] = $cdn['domain_name'];
        } elseif (!empty($cdn['status'])) {
            $row[] = sprintf('<comment>%s</comment>', ucfirst($cdn['status']));
        }

        $row[] = $environment->getPublicStoreDomainName();

        if (is_string($databaseServer)) {
            $headers[] = 'Database';
            $row[] = $databaseServer;
        }

        $this->output->horizontalTable($headers, [$row]);
    }

    /**
     * Get the information on the given environment's database.
     */
    protected function getEnvironmentDatabaseServer(string $environment): ?string
    {
        if (!$this->getProjectConfiguration()->exists()) {
            return null;
        }

        $databaseServerName = $this->getProjectConfiguration()->getEnvironment($environment)->getDatabaseServerName();

        if (!is_string($databaseServerName)) {
            return null;
        }

        if (!$this->databaseServers instanceof ResourceCollection) {
            $this->databaseServers = $this->apiClient->getDatabaseServers($this->getTeam());
        }

        $databaseServer = $this->databaseServers->firstWhereIdOrName($databaseServerName);

        if (!$databaseServer instanceof DatabaseServer) {
            return '<fg=red>Missing</>';
        }

        if ('available' !== $databaseServer->getStatus()) {
            return sprintf('<comment>%s</comment>', ucfirst($databaseServer->getStatus()));
        }

        return $databaseServer->getEndpoint() ?? '<fg=red>Unavailable</>';
    }
}
