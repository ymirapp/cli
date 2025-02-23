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

namespace Ymir\Cli\Command\Environment;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\Command\AbstractProjectCommand;

class CreateEnvironmentCommand extends AbstractProjectCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'environment:create';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Create a new environment')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the environment to create')
            ->addOption('use-image', null, InputOption::VALUE_NONE, 'Whether the environment will be deployed using a container image');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $name = $this->input->getStringArgument('name') ?: $this->output->ask('What is the name of the environment');
        $projectId = $this->projectConfiguration->getProjectId();
        $projectType = $this->projectConfiguration->getProjectType();

        $this->apiClient->createEnvironment($projectId, $name);

        $this->projectConfiguration->addEnvironment($name, $projectType->getEnvironmentConfiguration($name, $this->input->getBooleanOption('use-image') ? ['deployment' => 'image'] : []));

        $this->output->info('Environment created');
    }
}
