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
use Ymir\Cli\Command\Provider\ConnectProviderCommand;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Resource\NoResourcesFoundException;
use Ymir\Cli\Exception\Resource\ResourceNotFoundException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\CloudProvider;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Resource\Model\ResourceModelInterface;
use Ymir\Cli\Resource\Requirement\ActiveTeamRequirement;
use Ymir\Cli\Resource\Requirement\AwsCredentialsRequirement;
use Ymir\Cli\Resource\Requirement\NameRequirement;

class CloudProviderDefinition implements ProvisionableResourceDefinitionInterface, ResolvableResourceDefinitionInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getModelClass(): string
    {
        return CloudProvider::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequirements(): array
    {
        return [
            'active_team' => new ActiveTeamRequirement(),
            'name' => new NameRequirement('What is the name of the cloud provider connection being created?', 'AWS'),
            'credentials' => new AwsCredentialsRequirement(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceName(): string
    {
        return 'cloud provider';
    }

    /**
     * {@inheritdoc}
     */
    public function provision(ApiClient $apiClient, array $fulfilledRequirements): ?ResourceModelInterface
    {
        return $apiClient->createProvider($fulfilledRequirements['active_team'], $fulfilledRequirements['name'], $fulfilledRequirements['credentials']);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ExecutionContext $context, string $question): CloudProvider
    {
        $input = $context->getInput();
        $providerId = null;

        if ($input->hasArgument('provider')) {
            $providerId = $input->getNumericArgument('provider');
        } elseif ($input->hasOption('provider')) {
            $providerId = (int) $input->getNumericOption('provider');
        }

        $providers = $context->getApiClient()->getProviders($context->getTeam());

        if ($providers->isEmpty()) {
            throw new NoResourcesFoundException(sprintf('The currently active team has no cloud providers, but you can connect one with the "%s" command', ConnectProviderCommand::NAME));
        }

        $resolvedProvider = null;

        if (!empty($providerId)) {
            $resolvedProvider = $providers->firstWhereId($providerId);
        }

        if (!empty($providerId) && !$resolvedProvider instanceof CloudProvider) {
            throw new InvalidInputException(sprintf('The given provider "%s" isn\'t available to the currently active team', $providerId));
        } elseif ($resolvedProvider instanceof CloudProvider) {
            return $resolvedProvider;
        }

        $project = $context->getProject();

        if ($project instanceof Project) {
            return $project->getProvider();
        }

        $providerId = $context->getOutput()->choiceWithId($question, $providers->mapWithKeys(function (CloudProvider $provider) {
            return [$provider->getId() => $provider->getName()];
        }));
        $resolvedProvider = $providers->firstWhereId($providerId);

        if (!$resolvedProvider instanceof CloudProvider) {
            throw new ResourceNotFoundException($this->getResourceName(), $providerId);
        }

        return $resolvedProvider;
    }
}
