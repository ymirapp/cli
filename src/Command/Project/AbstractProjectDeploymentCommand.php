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

use Illuminate\Support\Collection;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Command\Email\CreateEmailIdentityCommand;
use Ymir\Cli\Command\Environment\GetEnvironmentUrlCommand;
use Ymir\Cli\Deployment\DeploymentStepInterface;
use Ymir\Cli\Project\Configuration\ProjectConfiguration;

abstract class AbstractProjectDeploymentCommand extends AbstractProjectCommand
{
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
    protected function perform()
    {
        $deployment = $this->createDeployment();
        $environment = $this->input->getStringArgument('environment');

        foreach ($this->deploymentSteps as $deploymentStep) {
            $deploymentStep->perform($deployment, $environment, $this->input, $this->output);
        }

        $this->output->info($this->getSuccessMessage($environment));

        $this->invoke(GetEnvironmentUrlCommand::NAME, ['environment' => $environment]);

        if (!array_key_exists('domain', $this->projectConfiguration->getEnvironment($environment))) {
            $this->output->newLine();
            $this->output->note(sprintf('You cannot send emails using the "<comment>ymirsites.com</comment>" domain. Please use the "<comment>%s</comment>" command to add an email address or domain for sending emails.', CreateEmailIdentityCommand::NAME));

            return;
        }

        $unmanagedDomains = collect($this->apiClient->getDeployment((int) $deployment->get('id'))->get('unmanaged_domains'));

        if ($unmanagedDomains->isEmpty()) {
            return;
        }

        $vanityDomainName = $this->apiClient->getEnvironmentVanityDomainName($this->projectConfiguration->getProjectId(), $environment);

        $unmanagedDomains = $unmanagedDomains->filter(function (string $unmanagedDomain) use ($vanityDomainName) {
            try {
                return !collect((array) dns_get_record($unmanagedDomain, DNS_CNAME))->contains('target', $vanityDomainName);
            } catch (\Throwable $exception) {
                return true;
            }
        });

        if ($unmanagedDomains->isEmpty()) {
            return;
        }

        $this->output->newLine();
        $this->output->warning('Not all domains in this project are managed by Ymir. The following DNS record(s) need to be manually added to your DNS server for these domains to work:');
        $this->output->newLine();
        $this->output->table(
            ['Type', 'Name', 'Value'],
            $unmanagedDomains->map(function (string $unmanagedDomain) use ($vanityDomainName) {
                return ['CNAME', $unmanagedDomain, $vanityDomainName];
            })->all()
        );
    }

    /**
     * Create the deployment and return its ID.
     */
    abstract protected function createDeployment(): Collection;

    /**
     * Get the message to display when a deployment was successful.
     */
    abstract protected function getSuccessMessage(string $environment): string;

    /**
     * Add a deployment step to the command.
     */
    private function addDeploymentStep(DeploymentStepInterface $deploymentStep)
    {
        $this->deploymentSteps[] = $deploymentStep;
    }
}
