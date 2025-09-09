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

namespace Ymir\Cli;

use Symfony\Component\DependencyInjection\ServiceLocator;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Project\ProjectConfiguration;
use Ymir\Cli\Project\ProjectLocator;
use Ymir\Cli\Resource\Model\Team;
use Ymir\Cli\Resource\ResourceProvisioner;
use Ymir\Cli\Team\TeamLocator;

class ExecutionContextFactory
{
    /**
     * The API client that interacts with the Ymir API.
     *
     * @var ApiClient
     */
    private $apiClient;

    /**
     * The path to the user's home directory.
     *
     * @var string
     */
    private $homeDirectory;

    /**
     * The Ymir project configuration.
     *
     * @var ProjectConfiguration
     */
    private $projectConfiguration;

    /**
     * The Ymir project directory.
     *
     * @var string
     */
    private $projectDirectory;

    /**
     * The Ymir project locator.
     *
     * @var ProjectLocator
     */
    private $projectLocator;

    /**
     * The resource provisioner.
     *
     * @var ResourceProvisioner
     */
    private $provisioner;

    /**
     * The resource definition locator.
     *
     * @var ServiceLocator
     */
    private $resourceDefinitionLocator;

    /**
     * The Ymir team locator.
     *
     * @var TeamLocator
     */
    private $teamLocator;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, string $homeDirectory, ProjectLocator $projectLocator, ProjectConfiguration $projectConfiguration, string $projectDirectory, ResourceProvisioner $provisioner, ServiceLocator $resourceDefinitionLocator, TeamLocator $teamLocator)
    {
        $this->apiClient = $apiClient;
        $this->homeDirectory = rtrim($homeDirectory, '/');
        $this->projectLocator = $projectLocator;
        $this->projectConfiguration = $projectConfiguration;
        $this->projectDirectory = rtrim($projectDirectory, '/');
        $this->provisioner = $provisioner;
        $this->resourceDefinitionLocator = $resourceDefinitionLocator;
        $this->teamLocator = $teamLocator;
    }

    /**
     * Create a new execution context.
     */
    public function create(Input $input, Output $output): ExecutionContext
    {
        return new ExecutionContext(
            $this->apiClient,
            $this->homeDirectory,
            $input,
            $this->resourceDefinitionLocator,
            $output,
            $this->projectLocator->getProject(),
            $this->projectConfiguration,
            $this->projectDirectory,
            $this->provisioner,
            $this->teamLocator->getTeam()
        );
    }
}
