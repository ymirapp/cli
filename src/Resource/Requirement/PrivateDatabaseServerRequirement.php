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

namespace Ymir\Cli\Resource\Requirement;

use Ymir\Cli\Exception\CommandCancelledException;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\Exception\Resource\RequirementDependencyException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\DatabaseServer;
use Ymir\Cli\Resource\Model\Network;

class PrivateDatabaseServerRequirement implements RequirementInterface
{
    /**
     * {@inheritdoc}
     */
    public function fulfill(ExecutionContext $context, array $fulfilledRequirements = []): bool
    {
        if (empty($fulfilledRequirements['network']) || !$fulfilledRequirements['network'] instanceof Network) {
            throw new RequirementDependencyException('"network" must be fulfilled before fulfilling the private database server requirement');
        } elseif (empty($fulfilledRequirements['type'])) {
            throw new RequirementDependencyException('"type" must be fulfilled before fulfilling the private database server requirement');
        }

        $hasNatGateway = $fulfilledRequirements['network']->hasNatGateway();
        $input = $context->getInput();
        $serverless = DatabaseServer::AURORA_DATABASE_TYPE === $fulfilledRequirements['type'];

        if ($input->getBooleanOption('public') && $serverless) {
            throw new InvalidInputException('You cannot use the "--public" option when creating a serverless database server');
        } elseif ($input->getBooleanOption('private')) {
            return true;
        }

        $output = $context->getOutput();

        if (!$hasNatGateway && $serverless && !$output->confirm('An Aurora serverless database cluster requires that Ymir add a NAT gateway (~$32/month) to your network. Would you like to proceed? <fg=default>(Answering "<comment>no</comment>" will cancel the command.)</>')) {
            throw new CommandCancelledException();
        }

        $private = $serverless || !$context->getOutput()->confirm('Should the database server be publicly accessible?');

        if (!$hasNatGateway && $private && !$output->confirm('A private database server requires that Ymir add a NAT gateway (~$32/month) to your network. Would you like to proceed? <fg=default>(Answering "<comment>no</comment>" will make the database server publicly accessible.)</>')) {
            $private = false;
        }

        return $private;
    }
}
