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

namespace Ymir\Cli\Resource;

use Ymir\Cli\Exception\CommandCancelledException;
use Ymir\Cli\Exception\LogicException;
use Ymir\Cli\Exception\Resource\FinalizationFailedException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Definition\FinalizableResourceDefinitionInterface;
use Ymir\Cli\Resource\Definition\ProvisionableResourceDefinitionInterface;
use Ymir\Cli\Resource\Model\ResourceModelInterface;
use Ymir\Cli\Resource\Requirement\RequirementInterface;
use Ymir\Sdk\Exception\ClientException;

class ResourceProvisioner
{
    /**
     * Provision a new resource by fulfilling the given definition requirements.
     *
     * This method fulfills all requirements needed by the resource definition, then provisions the resource and
     * finalizes it when supported. You can pass some pre-fulfilled requirements.
     */
    public function provision(ProvisionableResourceDefinitionInterface $definition, ExecutionContext $context, array $fulfilledRequirements = []): ?ResourceModelInterface
    {
        while (true) {
            $workingRequirements = $fulfilledRequirements;

            try {
                collect($definition->getRequirements())
                    ->except(array_keys($workingRequirements))
                    ->each(function (RequirementInterface $requirement, string $name) use ($context, &$workingRequirements): void {
                        $workingRequirements[$name] = $requirement->fulfill($context, $workingRequirements);
                    });

                $resource = $definition->provision($context->getApiClient(), $workingRequirements);
            } catch (ClientException $exception) {
                $output = $context->getOutput();

                $output->newLine();
                $output->exception($exception);

                if (!$context->getInput()->isInteractive() || !$output->confirm(sprintf('Failed to provision the %s. Do you want to retry?', $definition->getResourceName()))) {
                    throw new CommandCancelledException();
                }

                continue;
            }

            break;
        }

        if (!$definition instanceof FinalizableResourceDefinitionInterface) {
            return $resource;
        }

        if (!$resource instanceof ResourceModelInterface) {
            throw new LogicException('Initial provisioning must return a resource before it can be finalized');
        }

        return $this->finalize($definition, $context, $resource, $workingRequirements);
    }

    /**
     * Finalize the given provisioned resource.
     */
    private function finalize(FinalizableResourceDefinitionInterface $definition, ExecutionContext $context, ResourceModelInterface $resource, array $fulfilledRequirements): ResourceModelInterface
    {
        while (true) {
            try {
                return $definition->finalize($context, $resource, $fulfilledRequirements);
            } catch (FinalizationFailedException $exception) {
                $output = $context->getOutput();

                $output->newLine();
                $output->exception($exception);

                if (!$context->getInput()->isInteractive() || !$output->confirm(sprintf('Failed to finalize the %s. Do you want to retry?', $definition->getResourceName()))) {
                    throw new CommandCancelledException();
                }
            }
        }
    }
}
