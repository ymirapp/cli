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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Exception\Project\DeploymentFailedException;
use Ymir\Cli\Exception\Resource\ResourceStateException;
use Ymir\Cli\Resource\Model\Deployment;

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
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to rollback')
            ->addOption('select', null, InputOption::VALUE_NONE, 'Select the deployment to rollback to');
    }

    /**
     * {@inheritdoc}
     */
    protected function createDeployment(): Deployment
    {
        $environment = $this->getEnvironment();
        $project = $this->getProject();

        $deployments = $this->apiClient->getDeployments($project, $environment);

        if ($deployments->isEmpty()) {
            throw new ResourceStateException(sprintf('The "%s" environment has never been deployed to', $environment->getName()));
        }

        $deployments = $deployments->filter(function (Deployment $deployment) {
            return 'finished' === $deployment->getStatus();
        })->values();

        if ($deployments->count() < 2) {
            throw new ResourceStateException(sprintf('The "%s" environment has no previous successful deployments to rollback to', $environment->getName()));
        }

        $deploymentId = !$this->input->getBooleanOption('select') ? $deployments->get(1)->getId() : $this->output->choiceWithId('Which deployment would you like to rollback to?', $deployments->slice(1)->mapWithKeys(function (Deployment $deployment) {
            return [$deployment->getId() => $this->getDeploymentChoiceDisplayName($deployment)];
        }));

        $rollback = $this->apiClient->createRollback($project, $environment, $this->apiClient->getDeployment((int) $deploymentId));

        if (!$rollback->getId()) {
            throw new DeploymentFailedException('There was an error creating the rollback');
        }

        return $rollback;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEnvironmentQuestion(): string
    {
        return 'Which <comment>%s</comment> environment would you like to rollback?';
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
    private function getDeploymentChoiceDisplayName(Deployment $deployment): string
    {
        return sprintf('%s - %s (%s)', $deployment->getUuid(), $deployment->getInitiator() ? $deployment->getInitiator()->getName() : 'Unknown', Carbon::parse($deployment->getCreatedAt())->diffForHumans());
    }
}
