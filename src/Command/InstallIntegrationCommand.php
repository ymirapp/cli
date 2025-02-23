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

use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Project\Configuration\ProjectConfiguration;

class InstallIntegrationCommand extends AbstractProjectCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'install-integration';

    /**
     * The project directory where we want to install the plugin.
     *
     * @var string
     */
    private $projectDirectory;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, CliConfiguration $cliConfiguration, ProjectConfiguration $projectConfiguration, string $projectDirectory)
    {
        parent::__construct($apiClient, $cliConfiguration, $projectConfiguration);

        $this->projectDirectory = rtrim($projectDirectory, '/');
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Installs the Ymir integration for the project');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $projectType = $this->projectConfiguration->getProjectType();

        if ($projectType->isIntegrationInstalled($this->projectDirectory)) {
            $this->output->info('Ymir integration already installed');

            return;
        }

        $this->output->info(sprintf('Installing Ymir %s integration', $projectType->getName()));

        $projectType->installIntegration($this->projectDirectory);

        $this->output->info(sprintf('Ymir %s integration installed', $projectType->getName()));
    }
}
