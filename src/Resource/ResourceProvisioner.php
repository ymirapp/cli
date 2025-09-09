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
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Definition\ProvisionableResourceDefinitionInterface;
use Ymir\Cli\Resource\Model\ResourceModelInterface;
use Ymir\Cli\Resource\Requirement\RequirementInterface;
use Ymir\Sdk\Exception\ClientException;

class ResourceProvisioner
{
    /**
     * Provision a new resource by fulfilling the given definition requirements.
     *
     * This method fulfills all requirements needed by the resource definition, then calls the definition's provision
     * method with the fulfilled requirements. You can pass some pre-fulfilled requirements.
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

                return $definition->provision($context->getApiClient(), $workingRequirements);
            } catch (ClientException $exception) {
                $output = $context->getOutput();

                $output->newLine();
                $output->exception($exception);

                if (!$context->getInput()->isInteractive() || !$output->confirm(sprintf('Failed to provision the %s. Do you want to retry?', $definition->getResourceName()))) {
                    throw new CommandCancelledException();
                }
            }
        }
    }
}
