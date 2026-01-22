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

namespace Ymir\Cli\Resource\Definition;

use Ymir\Cli\ApiClient;
use Ymir\Cli\Command\Project\InitializeProjectCommand;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Resource\NoResourcesFoundException;
use Ymir\Cli\Exception\Resource\ResourceNotFoundException;
use Ymir\Cli\Exception\Resource\ResourceResolutionException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Resource\Model\ResourceModelInterface;
use Ymir\Cli\Resource\Requirement\CloudProviderRequirement;
use Ymir\Cli\Resource\Requirement\EnvironmentsRequirement;
use Ymir\Cli\Resource\Requirement\NameSlugRequirement;
use Ymir\Cli\Resource\Requirement\RegionRequirement;

class ProjectDefinition implements ProvisionableResourceDefinitionInterface, ResolvableResourceDefinitionInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getModelClass(): string
    {
        return Project::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getRequirements(): array
    {
        return [
            'name' => new NameSlugRequirement('What is the name of the project being created?'),
            'provider' => new CloudProviderRequirement('Which cloud provider should the project be on?'),
            'region' => new RegionRequirement('Which region should the project be created in?'),
            'environments' => new EnvironmentsRequirement(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceName(): string
    {
        return 'project';
    }

    /**
     * {@inheritDoc}
     */
    public function provision(ApiClient $apiClient, array $fulfilledRequirements): ?ResourceModelInterface
    {
        return $apiClient->createProject($fulfilledRequirements['provider'], $fulfilledRequirements['name'], $fulfilledRequirements['region'], $fulfilledRequirements['environments']);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ExecutionContext $context, string $question): Project
    {
        $input = $context->getInput();
        $project = $context->getProject();
        $projectIdOrName = null;

        if ($input->hasArgument('project')) {
            $projectIdOrName = $input->getStringArgument('project');
        }

        if (empty($projectIdOrName) && $project instanceof Project) {
            return $project;
        }

        $projects = $context->getApiClient()->getProjects($context->getTeam());

        if ($projects->isEmpty()) {
            throw new NoResourcesFoundException(sprintf('The currently active team has no projects, but you can create one with the "%s" command', InitializeProjectCommand::NAME));
        } elseif (empty($projectIdOrName)) {
            $projectIdOrName = $context->getOutput()->choiceWithResourceDetails($question, $projects);
        }

        if (empty($projectIdOrName)) {
            throw new InvalidInputException('You must provide a valid project ID or name');
        } elseif (!is_numeric($projectIdOrName) && 1 < $projects->whereName($projectIdOrName)->count()) {
            throw new ResourceResolutionException(sprintf('Unable to select a project because more than one project has the name "%s"', $projectIdOrName));
        }

        $resolvedProject = $projects->firstWhereIdOrName($projectIdOrName);

        if (!$resolvedProject instanceof Project) {
            throw new ResourceNotFoundException($this->getResourceName(), $projectIdOrName);
        }

        return $resolvedProject;
    }
}
