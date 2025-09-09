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

namespace Ymir\Cli\Command;

class InstallIntegrationCommand extends AbstractCommand implements LocalProjectCommandInterface
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'install-integration';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Install the Ymir integration for the project type');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $projectType = $this->getProjectConfiguration()->getProjectType();

        if ($projectType->isIntegrationInstalled($this->getProjectDirectory())) {
            $this->output->info(sprintf('The Ymir integration for %s is already installed', $projectType->getName()));

            return;
        }

        $this->output->info(sprintf('Installing the Ymir integration for %s', $projectType->getName()));

        $projectType->installIntegration($this->getProjectDirectory());

        $this->output->info(sprintf('Ymir %s integration installed successfully', $projectType->getName()));
    }
}
