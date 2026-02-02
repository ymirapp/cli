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

use Ymir\Cli\Command\Email\CreateEmailIdentityCommand;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Resource\NoResourcesFoundException;
use Ymir\Cli\Exception\Resource\ResourceNotFoundException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\EmailIdentity;

class EmailIdentityDefinition implements ResolvableResourceDefinitionInterface
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
    public function getResourceName(): string
    {
        return 'email identity';
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
}
