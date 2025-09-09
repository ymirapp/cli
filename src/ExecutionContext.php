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
use Ymir\Cli\Command\Team\SelectTeamCommand;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Exception\LogicException;
use Ymir\Cli\Exception\RuntimeException;
use Ymir\Cli\Project\ProjectConfiguration;
use Ymir\Cli\Resource\Definition\ProvisionableResourceDefinitionInterface;
use Ymir\Cli\Resource\Definition\ResolvableResourceDefinitionInterface;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Resource\Model\ResourceModelInterface;
use Ymir\Cli\Resource\Model\Team;
use Ymir\Cli\Resource\Requirement\RequirementInterface;
use Ymir\Cli\Resource\ResourceProvisioner;

class ExecutionContext
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
     * The console input.
     *
     * @var Input
     */
    private $input;

    /**
     * The resource definition locator.
     *
     * @var ServiceLocator
     */
    private $locator;

    /**
     * The console output.
     *
     * @var Output
     */
    private $output;

    /**
     * The parent resource model.
     *
     * @var ResourceModelInterface|null
     */
    private $parentResource;

    /**
     * The project associated with the local configuration file.
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

    /**
     * The project directory.
     *
     * @var string
     */
    private $projectDirectory;

    /**
     * The resource provisioner.
     *
     * @var ResourceProvisioner
     */
    private $provisioner;

    /**
     * The currently active team.
     *
     * @var Team|null
     */
    private $team;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, string $homeDirectory, Input $input, ServiceLocator $locator, Output $output, ?Project $project, ProjectConfiguration $projectConfiguration, string $projectDirectory, ResourceProvisioner $provisioner, ?Team $team)
    {
        $this->apiClient = $apiClient;
        $this->homeDirectory = $homeDirectory;
        $this->input = $input;
        $this->locator = $locator;
        $this->output = $output;
        $this->parentResource = null;
        $this->project = $project;
        $this->projectConfiguration = $projectConfiguration;
        $this->projectDirectory = $projectDirectory;
        $this->provisioner = $provisioner;
        $this->team = $team;
    }

    /**
     * Fulfill a specific requirement using the current context.
     */
    public function fulfill(RequirementInterface $requirement, array $fulfilledRequirements = [])
    {
        return $requirement->fulfill($this, $fulfilledRequirements);
    }

    /**
     * Get the Ymir API client.
     */
    public function getApiClient(): ApiClient
    {
        return $this->apiClient;
    }

    /**
     * Get the path to the user's home directory.
     */
    public function getHomeDirectory(): string
    {
        return $this->homeDirectory;
    }

    /**
     * Get the console input.
     */
    public function getInput(): Input
    {
        return $this->input;
    }

    /**
     * Get the console output.
     */
    public function getOutput(): Output
    {
        return $this->output;
    }

    /**
     * Get the parent resource model.
     */
    public function getParentResource(): ?ResourceModelInterface
    {
        return $this->parentResource;
    }

    /**
     * Get the project associated with the local configuration file.
     */
    public function getProject(): ?Project
    {
        return $this->project;
    }

    /**
     * Get the Ymir project configuration.
     */
    public function getProjectConfiguration(): ProjectConfiguration
    {
        return $this->projectConfiguration;
    }

    /**
     * Get the project directory.
     */
    public function getProjectDirectory(): string
    {
        return $this->projectDirectory;
    }

    /**
     * Get the project associated with the local configuration file or throw an exception if there isn't one.
     */
    public function getProjectOrFail(): Project
    {
        if (!$this->project instanceof Project) {
            throw new RuntimeException('No Ymir project found in the current directory');
        }

        return $this->project;
    }

    /**
     * Get the resource provisioner.
     */
    public function getProvisioner(): ResourceProvisioner
    {
        return $this->provisioner;
    }

    /**
     * Get the currently active team or null if none is selected.
     */
    public function getTeam(): ?Team
    {
        return $this->team;
    }

    /**
     * Get the currently active team or throw an exception if there isn't one.
     */
    public function getTeamOrFail(): Team
    {
        if (!$this->team instanceof Team) {
            throw new RuntimeException(sprintf('You do not have a currently active team, but you can select a team using the "%s" command', SelectTeamCommand::NAME));
        }

        return $this->team;
    }

    /**
     * Provision a new resource using the given resource model class.
     *
     * @template T of ResourceModelInterface
     *
     * @param class-string<T> $resourceClass
     *
     * @return T|null
     */
    public function provision(string $resourceClass, array $fulfilledRequirements = []): ?ResourceModelInterface
    {
        $definition = $this->locator->get($resourceClass);

        if (!$definition instanceof ProvisionableResourceDefinitionInterface) {
            throw new LogicException(sprintf('The resource definition for "%s" doesn\'t implement the "%s" interface', $resourceClass, ProvisionableResourceDefinitionInterface::class));
        }

        return $this->provisioner->provision($definition, $this, $fulfilledRequirements);
    }

    /**
     * Resolve an existing resource using the given resource model class.
     *
     * @template T of ResourceModelInterface
     *
     * @param class-string<T> $resourceClass
     *
     * @return T
     */
    public function resolve(string $resourceClass, string $question): ResourceModelInterface
    {
        $definition = $this->locator->get($resourceClass);

        if (!$definition instanceof ResolvableResourceDefinitionInterface) {
            throw new LogicException(sprintf('The resource definition for "%s" doesn\'t implement the "%s" interface', $resourceClass, ResolvableResourceDefinitionInterface::class));
        }

        return $definition->resolve($this, $this->prepareQuestion($question));
    }

    /**
     * Creates a new context with the given parent resource.
     */
    public function withParentResource(ResourceModelInterface $parentResource): self
    {
        $new = clone $this;

        $new->parentResource = $parentResource;

        return $new;
    }

    /**
     * Creates a new context with the given project.
     */
    public function withProject(Project $project): self
    {
        $new = clone $this;

        $new->project = $project;

        return $new;
    }

    /**
     * Prepares the question by injecting the parent resource name into the placeholder.
     */
    private function prepareQuestion(string $question): string
    {
        if (!str_contains($question, '%s')) {
            return $question;
        }

        $parent = $this->getParentResource() ?? $this->getProject();

        return $parent instanceof ResourceModelInterface ? sprintf($question, $parent->getName()) : $question;
    }
}
