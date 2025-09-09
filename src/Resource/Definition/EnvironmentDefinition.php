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
use Ymir\Cli\Command\Environment\CreateEnvironmentCommand;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\LogicException;
use Ymir\Cli\Exception\Resource\NoResourcesFoundException;
use Ymir\Cli\Exception\Resource\ResourceNotFoundException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Resource\Model\ResourceModelInterface;
use Ymir\Cli\Resource\Requirement\NameSlugRequirement;
use Ymir\Cli\Resource\Requirement\ProjectRequirement;

class EnvironmentDefinition implements ProvisionableResourceDefinitionInterface, ResolvableResourceDefinitionInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getModelClass(): string
    {
        return Environment::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequirements(): array
    {
        return [
            'project' => new ProjectRequirement(),
            'name' => new NameSlugRequirement('What is the name of the environment being created?'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceName(): string
    {
        return 'environment';
    }

    /**
     * {@inheritdoc}
     */
    public function provision(ApiClient $apiClient, array $fulfilledRequirements): ?ResourceModelInterface
    {
        return $apiClient->createEnvironment($fulfilledRequirements['project'], $fulfilledRequirements['name']);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ExecutionContext $context, string $question): Environment
    {
        $input = $context->getInput();
        $project = $context->getParentResource() ?? $context->getProject();

        if (!$project instanceof Project) {
            throw new LogicException('A project must be resolved and passed into the context before resolving an environment');
        }

        $environments = $context->getApiClient()->getEnvironments($project)->values();

        if ($environments->isEmpty()) {
            throw new NoResourcesFoundException(sprintf('The "%s" project has no environments, but you can create one with the "%s" command', $project->getName(), CreateEnvironmentCommand::NAME));
        }

        $environmentName = null;

        if ($input->hasArgument('environment')) {
            $environmentName = $input->getStringArgument('environment');
        } elseif ($input->hasOption('environment')) {
            $environmentName = $input->getStringOption('environment', true);
        }

        if (empty($environmentName)) {
            $environmentName = $context->getOutput()->choice($question, $environments->map(function (Environment $environment) {
                return $environment->getName();
            }));
        }

        if (empty($environmentName)) {
            throw new InvalidInputException('You must provide a valid environment name');
        }

        $resolvedEnvironment = $environments->firstWhereName($environmentName);

        if (!$resolvedEnvironment instanceof Environment) {
            throw new ResourceNotFoundException($this->getResourceName(), $environmentName);
        }

        return $resolvedEnvironment;
    }
}
