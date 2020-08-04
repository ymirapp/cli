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
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Command\Email\CreateEmailIdentityCommand;
use Ymir\Cli\Command\Environment\GetEnvironmentUrlCommand;
use Ymir\Cli\Console\OutputStyle;
use Ymir\Cli\Deployment\DeploymentStepInterface;
use Ymir\Cli\ProjectConfiguration;

class DeployProjectCommand extends AbstractProjectCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'project:deploy';

    /**
     * The deployment steps to perform.
     *
     * @var DeploymentStepInterface[]
     */
    private $deploymentSteps;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, CliConfiguration $cliConfiguration, ProjectConfiguration $projectConfiguration, array $deploymentSteps = [])
    {
        parent::__construct($apiClient, $cliConfiguration, $projectConfiguration);

        $this->deploymentSteps = [];

        foreach ($deploymentSteps as $deploymentStep) {
            $this->addDeploymentStep($deploymentStep);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setAliases(['deploy'])
            ->setDescription('Deploy project to an environment')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The environment name', 'staging');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputStyle $output)
    {
        $environment = $this->getStringArgument($input, 'environment');

        $this->invoke($output, ValidateProjectCommand::NAME, ['environments' => $environment]);
        $this->invoke($output, BuildProjectCommand::NAME, ['environment' => $environment]);

        $deploymentId = (int) $this->apiClient->createDeployment($this->projectConfiguration->getProjectId(), $environment, $this->projectConfiguration)->get('id');

        if (empty($deploymentId)) {
            throw new RuntimeException('There was an error creating the deployment');
        }

        foreach ($this->deploymentSteps as $deploymentStep) {
            $deploymentStep->perform($deploymentId, $output);
        }

        $output->info(sprintf('Project deployed successfully to "<comment>%s</comment>" environment', $environment));

        $this->invoke($output, GetEnvironmentUrlCommand::NAME, ['environment' => $environment]);

        if ($this->apiClient->getEmailIdentities($this->cliConfiguration->getActiveTeamId())->isEmpty()) {
            $output->newLine();
            $output->writeln(sprintf('<comment>Note:</comment> You cannot send emails using the "<comment>ymirsites.com</comment>" domain. Please use the "<comment>%s</comment>" command to add an email address or domain for sending emails.', CreateEmailIdentityCommand::NAME));
        }
    }

    /**
     * Add a deployment step to the command.
     */
    private function addDeploymentStep(DeploymentStepInterface $deploymentStep)
    {
        $this->deploymentSteps[] = $deploymentStep;
    }
}
