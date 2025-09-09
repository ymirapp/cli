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

namespace Ymir\Cli\Team;

use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Resource\Model\Team;

class TeamLocator
{
    /**
     * The API client that interacts with the Ymir API.
     *
     * @var ApiClient
     */
    private $apiClient;

    /**
     * The global Ymir CLI configuration.
     *
     * @var CliConfiguration
     */
    private $cliConfiguration;

    /**
     * The currently active team.
     *
     * @var Team|null
     */
    private $team;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, CliConfiguration $cliConfiguration)
    {
        $this->apiClient = $apiClient;
        $this->cliConfiguration = $cliConfiguration;
        $this->team = null;
    }

    /**
     * Get the currently active team.
     */
    public function getTeam(): ?Team
    {
        $teamId = $this->cliConfiguration->getActiveTeamId();

        if (null === $this->team && !empty($teamId)) {
            $this->team = $this->apiClient->getTeam($teamId);
        }

        return $this->team;
    }
}
