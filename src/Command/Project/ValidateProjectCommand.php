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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Console\OutputStyle;
use Ymir\Cli\ProjectConfiguration;

class ValidateProjectCommand extends AbstractCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'project:validate';

    /**
     * The Ymir project configuration.
     *
     * @var ProjectConfiguration
     */
    private $projectConfiguration;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, CliConfiguration $cliConfiguration, ProjectConfiguration $projectConfiguration)
    {
        parent::__construct($apiClient, $cliConfiguration);

        $this->projectConfiguration = $projectConfiguration;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setAliases(['validate'])
            ->setDescription('Validates the project configuration')
            ->addArgument('environments', InputArgument::OPTIONAL, 'The environments to validate');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $environments = $input->getArgument('environments');

        if (!is_array($environments)) {
            $environments = (array) $environments;
        }

        $this->projectConfiguration->validate($environments);

        if (empty($environments)) {
            $environments = $this->projectConfiguration->getEnvironments();
        }

        $projectId = $this->projectConfiguration->getProjectId();

        foreach ($environments as $environment) {
            $this->apiClient->validateProjectConfiguration($projectId, $environment, $this->projectConfiguration);
        }

        $output->info('Project configuration is valid');
    }
}
