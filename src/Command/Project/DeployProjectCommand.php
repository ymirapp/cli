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

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Tightenco\Collect\Support\Collection;
use Ymir\Cli\Console\ConsoleOutput;

class DeployProjectCommand extends AbstractProjectDeploymentCommand
{
    /**
     * The alias of the command.
     *
     * @var string
     */
    public const ALIAS = 'deploy';

    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'project:deploy';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Deploy project to an environment')
            ->setAliases([self::ALIAS])
            ->addArgument('environment', InputArgument::OPTIONAL, 'The environment name', 'staging');
    }

    /**
     * {@inheritdoc}
     */
    protected function createDeployment(InputInterface $input, ConsoleOutput $output): Collection
    {
        $environment = $this->getStringArgument($input, 'environment');
        $projectId = $this->projectConfiguration->getProjectId();

        $this->invoke($output, ValidateProjectCommand::NAME, ['environments' => $environment]);
        $this->invoke($output, BuildProjectCommand::NAME, ['environment' => $environment]);

        $deployment = $this->apiClient->createDeployment($projectId, $environment, $this->projectConfiguration);

        if (!$deployment->has('id')) {
            throw new RuntimeException('There was an error creating the deployment');
        }

        return $deployment;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSuccessMessage(string $environment): string
    {
        return sprintf('Project deployed successfully to "<comment>%s</comment>" environment', $environment);
    }
}
