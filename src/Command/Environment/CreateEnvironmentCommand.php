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
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Command\LocalProjectCommandInterface;
use Ymir\Cli\Project\Configuration\ImageDeploymentConfigurationChange;
use Ymir\Cli\Resource\Model\Environment;

class CreateEnvironmentCommand extends AbstractCommand implements LocalProjectCommandInterface
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
            ->addOption('no-image', null, InputOption::VALUE_NONE, 'Deploy the environment using a zip archive instead of a container image');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $environment = $this->provision(Environment::class, [], $this->getProject());
        $projectType = $this->getProjectConfiguration()->getProjectType();

        $configuration = $projectType->generateEnvironmentConfiguration($environment->getName());

        if (!$this->input->getOption('no-image')) {
            $configuration = (new ImageDeploymentConfigurationChange())->apply($configuration, $projectType);
        }

        $this->getProjectConfiguration()->addEnvironment($configuration);

        $this->output->info('Environment created');
    }
}
