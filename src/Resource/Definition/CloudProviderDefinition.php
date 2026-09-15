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
use Ymir\Cli\Command\Provider\DeleteProviderCommand;
use Ymir\Cli\Command\Provider\ListProvidersCommand;
use Ymir\Cli\Command\Provider\UpdateProviderCommand;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\LogicException;
use Ymir\Cli\Exception\Resource\FinalizationFailedException;
use Ymir\Cli\Exception\Resource\NoResourcesFoundException;
use Ymir\Cli\Exception\Resource\ProvisioningFailedException;
use Ymir\Cli\Exception\Resource\ResourceNotFoundException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\CloudProvider;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Resource\Model\ResourceModelInterface;
use Ymir\Cli\Resource\Requirement\ActiveTeamRequirement;
use Ymir\Cli\Resource\Requirement\CloudProviderAuthenticationMethodRequirement;
use Ymir\Cli\Resource\Requirement\CloudProviderCredentialsRequirement;
use Ymir\Cli\Resource\Requirement\NameRequirement;

class CloudProviderDefinition implements FinalizableResourceDefinitionInterface, ResolvableResourceDefinitionInterface
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
    public function finalize(ExecutionContext $context, ResourceModelInterface $resource, array $fulfilledRequirements): ResourceModelInterface
    {
        if (!$resource instanceof CloudProvider) {
            throw new LogicException('Cloud provider provisioning must return a cloud provider');
        }

        try {
            $credentials = $context->fulfill(new CloudProviderCredentialsRequirement($resource), $fulfilledRequirements);
        } catch (\Throwable $exception) {
            throw new ProvisioningFailedException(sprintf('Failed to prepare authentication for the pending cloud provider (ID: %1$d): %2$s. Retry with the "%3$s %1$d" command or delete it with the "%4$s %1$d" command', $resource->getId(), $exception->getMessage(), UpdateProviderCommand::NAME, DeleteProviderCommand::NAME), $exception->getCode(), $exception);
        }

        try {
            $context->getApiClient()->updateProvider($resource, $credentials);
        } catch (\Throwable $exception) {
            throw new FinalizationFailedException(sprintf('Failed to connect the pending cloud provider (ID: %1$d): %2$s. Retry with the "%3$s %1$d" command or delete it with the "%4$s %1$d" command', $resource->getId(), $exception->getMessage(), UpdateProviderCommand::NAME, DeleteProviderCommand::NAME), $exception->getCode(), $exception);
        }

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequirements(): array
    {
        return [
            'active_team' => new ActiveTeamRequirement(),
            'name' => new NameRequirement('What is the name of the cloud provider connection being created?', 'AWS'),
            'authentication_method' => new CloudProviderAuthenticationMethodRequirement('Which authentication method would you like to use?'),
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
        return $apiClient->createProvider($fulfilledRequirements['active_team'], $fulfilledRequirements['name']);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ExecutionContext $context, string $question, array $fulfilledRequirements = []): CloudProvider
    {
        $input = $context->getInput();
        $project = $context->getProject();
        $providerId = null;
        $requiredStatus = $fulfilledRequirements['status'] ?? null;

        if ($input->hasArgument('provider')) {
            $providerId = $input->getNumericArgument('provider', !$project instanceof Project);
        } elseif ($input->hasOption('provider')) {
            $providerId = (int) $input->getNumericOption('provider', !$project instanceof Project);
        }

        $providers = $context->getApiClient()->getProviders($context->getTeam());

        if ($providers->isEmpty()) {
            throw new NoResourcesFoundException(sprintf('The currently active team has no cloud providers, but you can connect one with the "%s" command', ConnectProviderCommand::NAME));
        }

        $resolvedProvider = !empty($providerId) ? $providers->firstWhereId($providerId) : null;

        if (!empty($providerId) && !$resolvedProvider instanceof CloudProvider) {
            throw new InvalidInputException(sprintf('The given provider "%s" isn\'t available to the currently active team', $providerId));
        }

        if (empty($providerId) && $project instanceof Project) {
            $resolvedProvider = $project->getProvider();
        }

        if ($resolvedProvider instanceof CloudProvider && is_string($requiredStatus) && $requiredStatus !== $resolvedProvider->getStatus()) {
            throw new InvalidInputException(sprintf('The "%s" cloud provider connection (ID: %d) cannot be used for operations because its status is "%s", but you can complete or repair it in Ymir or use the "%s" command to find a connected connection', $resolvedProvider->getName(), $resolvedProvider->getId(), $resolvedProvider->getStatus(), ListProvidersCommand::NAME));
        }

        if ($resolvedProvider instanceof CloudProvider) {
            return $resolvedProvider;
        }

        if (!empty($requiredStatus)) {
            $providers = $providers->filter(function (CloudProvider $provider) use ($requiredStatus): bool {
                return $requiredStatus === $provider->getStatus();
            });
        }

        if ($providers->isEmpty()) {
            throw new NoResourcesFoundException(sprintf('The currently active team has no connected cloud provider connections, but you can inspect their status with the "%s" command and complete or repair one in Ymir', ListProvidersCommand::NAME));
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
