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
use Ymir\Cli\Command\Email\CreateEmailIdentityCommand;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\LogicException;
use Ymir\Cli\Exception\Resource\NoResourcesFoundException;
use Ymir\Cli\Exception\Resource\ResourceNotFoundException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\EmailIdentity;
use Ymir\Cli\Resource\Model\ResourceModelInterface;
use Ymir\Cli\Resource\Requirement\ConnectedCloudProviderRequirement;
use Ymir\Cli\Resource\Requirement\NameRequirement;
use Ymir\Cli\Resource\Requirement\RegionRequirement;

class EmailIdentityDefinition implements FinalizableResourceDefinitionInterface, ResolvableResourceDefinitionInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getModelClass(): string
    {
        return EmailIdentity::class;
    }

    /**
     * {@inheritdoc}
     */
    public function finalize(ExecutionContext $context, ResourceModelInterface $resource, array $fulfilledRequirements): ResourceModelInterface
    {
        if (!$resource instanceof EmailIdentity) {
            throw new LogicException('Email identity provisioning must return an email identity');
        }

        $output = $context->getOutput();
        $output->info('Email identity created');

        if ('domain' === $resource->getType()) {
            $this->displayDkimAuthenticationRecords($output, $resource);
        } elseif ('email' === $resource->getType()) {
            $output->newLine();
            $output->important(sprintf('A verification email was sent to %s to validate the email identity', $resource->getName()));
        }

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequirements(): array
    {
        return [
            'name' => new NameRequirement('What is the name of the email identity being created?'),
            'provider' => new ConnectedCloudProviderRequirement('Which cloud provider would you like to create the email identity on?'),
            'region' => new RegionRequirement('Which region should the email identity be created in?'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceName(): string
    {
        return 'email identity';
    }

    /**
     * {@inheritdoc}
     */
    public function provision(ApiClient $apiClient, array $fulfilledRequirements): ?ResourceModelInterface
    {
        return $apiClient->createEmailIdentity($fulfilledRequirements['provider'], $fulfilledRequirements['name'], $fulfilledRequirements['region']);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ExecutionContext $context, string $question, array $fulfilledRequirements = []): EmailIdentity
    {
        $input = $context->getInput();
        $identityIdOrName = $input->getStringArgument('identity');

        $identities = $context->getApiClient()->getEmailIdentities($context->getTeam());

        if ($identities->isEmpty()) {
            throw new NoResourcesFoundException(sprintf('The currently active team has no email identities, but you can create one with the "%s" command', CreateEmailIdentityCommand::NAME));
        } elseif (empty($identityIdOrName)) {
            $identityIdOrName = $context->getOutput()->choice($question, $identities->map(function (EmailIdentity $identity) {
                return $identity->getName();
            }));
        }

        if (empty($identityIdOrName)) {
            throw new InvalidInputException('You must provide a valid email identity ID or name');
        }

        $resolvedIdentity = $identities->firstWhereIdOrName($identityIdOrName);

        if (!$resolvedIdentity instanceof EmailIdentity) {
            throw new ResourceNotFoundException($this->getResourceName(), $identityIdOrName);
        }

        return $resolvedIdentity;
    }

    /**
     * Managed email identities do not need their DKIM records displayed.
     */
    private function displayDkimAuthenticationRecords(Output $output, EmailIdentity $identity): void
    {
        $dkimRecords = $identity->getDkimAuthenticationRecords();

        if (empty($dkimRecords) || $identity->isManaged()) {
            return;
        }

        $output->newLine();
        $output->important('The following DNS records needs to exist on your DNS server at all times to verify the email identity and authenticate its DKIM signature:');
        $output->newLine();
        $output->table(
            ['Name', 'Type', 'Value'],
            collect($dkimRecords)->map(function (array $dkimRecord) {
                return [
                    $dkimRecord['name'],
                    strtoupper($dkimRecord['type']),
                    $dkimRecord['value'],
                ];
            })->all()
        );
    }
}
