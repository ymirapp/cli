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

namespace Ymir\Cli\Project;

use Ymir\Cli\ApiClient;
use Ymir\Cli\Resource\Model\Project;

class ProjectLocator
{
    /**
     * The API client that interacts with the Ymir API.
     *
     * @var ApiClient
     */
    private $apiClient;

    /**
     * The located Project resource.
     *
     * @var Project|null
     */
    private $project;

    /**
     * The Ymir project configuration.
     *
     * @var ProjectConfiguration
     */
    private $projectConfiguration;

    public function __construct(ApiClient $apiClient, ProjectConfiguration $projectConfiguration)
    {
        $this->apiClient = $apiClient;
        $this->project = null;
        $this->projectConfiguration = $projectConfiguration;
    }

    /**
     * Get the project associated with the local configuration file.
     */
    public function getProject(): ?Project
    {
        if (null === $this->project && $this->projectConfiguration->exists()) {
            $this->project = $this->apiClient->getProject($this->projectConfiguration->getProjectId());
        }

        return $this->project;
    }
}
