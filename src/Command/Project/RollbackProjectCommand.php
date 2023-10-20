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

namespace Ymir\Cli\Command\Project;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Support\Arr;

class RollbackProjectCommand extends AbstractProjectDeploymentCommand
{
    /**
     * The alias of the command.
     *
     * @var string
     */
    public const ALIAS = 'rollback';

    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'project:rollback';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Rollback environment to a previous deployment')
            ->setAliases([self::ALIAS])
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to rollback', 'staging')
            ->addOption('select', null, InputOption::VALUE_NONE, 'Select the deployment to rollback to');
    }

    /**
     * {@inheritdoc}
     */
    protected function createDeployment(Input $input, Output $output): Collection
    {
        $environment = $input->getStringArgument('environment');
        $projectId = $this->projectConfiguration->getProjectId();

        $deployments = $this->apiClient->getDeployments($projectId, $environment);

        if ($deployments->isEmpty()) {
            throw new RuntimeException(sprintf('The "%s" environment has never been deployed to', $environment));
        }

        $deployments = $deployments->where('status', 'finished')->values();

        if ($deployments->isEmpty()) {
            throw new RuntimeException(sprintf('The "%s" environment has no successful deployments to rollback to', $environment));
        }

        $deploymentId = !$input->getBooleanOption('select') ? $deployments[0]['id'] : $output->choice('Please select a deployment to rollback to', $deployments->mapWithKeys(function (array $deployment) {
            return [$deployment['id'] => $this->getDeploymentChoiceDisplayName($deployment)];
        }));

        $rollback = $this->apiClient->createRollback($this->projectConfiguration->getProjectId(), $environment, (int) $deploymentId);

        if (!$rollback->has('id')) {
            throw new RuntimeException('There was an error creating the rollback');
        }

        return $rollback;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSuccessMessage(string $environment): string
    {
        return sprintf('Project "<comment>%s</comment>" environment rolled back successfully', $environment);
    }

    /**
     * Get the display name for the deployment for the choice question.
     */
    private function getDeploymentChoiceDisplayName(array $deployment): string
    {
        return sprintf('%s - %s (%s)', $deployment['uuid'], Arr::get($deployment, 'initiator.name'), Carbon::parse($deployment['created_at'])->diffForHumans());
    }
}
