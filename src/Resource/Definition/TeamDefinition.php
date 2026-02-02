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
use Ymir\Cli\Command\Team\CreateTeamCommand;
use Ymir\Cli\Exception\Resource\NoResourcesFoundException;
use Ymir\Cli\Exception\Resource\ResourceNotFoundException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\Resource\Model\ResourceModelInterface;
use Ymir\Cli\Resource\Model\Team;
use Ymir\Cli\Resource\Requirement\NameRequirement;

class TeamDefinition implements ProvisionableResourceDefinitionInterface, ResolvableResourceDefinitionInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getModelClass(): string
    {
        return Team::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequirements(): array
    {
        return [
            'name' => new NameRequirement('What is the name of the team being created?'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceName(): string
    {
        return 'team';
    }

    /**
     * {@inheritdoc}
     */
    public function provision(ApiClient $apiClient, array $fulfilledRequirements): ?ResourceModelInterface
    {
        return $apiClient->createTeam($fulfilledRequirements['name']);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ExecutionContext $context, string $question, array $fulfilledRequirements = []): Team
    {
        $input = $context->getInput();
        $teamId = null;

        if ($input->hasArgument('team')) {
            $teamId = $input->getNumericArgument('team');
        } elseif ($input->hasOption('team')) {
            $teamId = (int) $input->getNumericOption('team');
        }

        $teams = $context->getApiClient()->getTeams();

        if ($teams->isEmpty()) {
            throw new NoResourcesFoundException(sprintf('You are not a member of any teams, but you can create one with the "%s" command', CreateTeamCommand::NAME));
        }

        if (empty($teamId)) {
            $teamId = $context->getOutput()->choiceWithId($question, $teams->mapWithKeys(function (Team $team) {
                return [$team->getId() => $team->getName()];
            }));
        }

        $resolvedTeam = $teams->firstWhereId($teamId);

        if (!$resolvedTeam instanceof Team) {
            throw new ResourceNotFoundException($this->getResourceName(), $teamId);
        }

        return $resolvedTeam;
    }
}
