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

class RedeployProjectCommand extends AbstractProjectDeploymentCommand
{
    /**
     * The alias of the command.
     *
     * @var string
     */
    public const ALIAS = 'redeploy';

    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'project:redeploy';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Redeploy project to an environment')
            ->setAliases([self::ALIAS])
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to redeploy', 'staging');
    }

    /**
     * {@inheritdoc}
     */
    protected function createDeployment(InputInterface $input, ConsoleOutput $output): Collection
    {
        $redeployment = $this->apiClient->createRedeployment($this->projectConfiguration->getProjectId(), $this->getStringArgument($input, 'environment'));

        if (!$redeployment->has('id')) {
            throw new RuntimeException('There was an error creating the redeployment');
        }

        return $redeployment;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSuccessMessage(string $environment): string
    {
        return sprintf('Project redeployed successfully to "<comment>%s</comment>" environment', $environment);
    }
}
