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
use Symfony\Component\Console\Input\InputInterface;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Command\Email\CreateEmailIdentityCommand;
use Ymir\Cli\Command\Environment\GetEnvironmentUrlCommand;
use Ymir\Cli\Console\OutputInterface;
use Ymir\Cli\Deployment\DeploymentStepInterface;
use Ymir\Cli\ProjectConfiguration\ProjectConfiguration;

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
    protected function perform(InputInterface $input, OutputInterface $output)
    {
        $deployment = $this->createDeployment($input, $output);
        $environment = $this->getStringArgument($input, 'environment');

        foreach ($this->deploymentSteps as $deploymentStep) {
            $deploymentStep->perform($deployment, $output);
        }

        $output->info($this->getSuccessMessage($environment));

        $this->invoke($output, GetEnvironmentUrlCommand::NAME, ['environment' => $environment]);

        if (!array_key_exists('domain', $this->projectConfiguration->getEnvironment($environment))) {
            $output->newLine();
            $output->note(sprintf('You cannot send emails using the "<comment>ymirsites.com</comment>" domain. Please use the "<comment>%s</comment>" command to add an email address or domain for sending emails.', CreateEmailIdentityCommand::NAME));

            return;
        }

        $unmanagedDomains = collect($this->apiClient->getDeployment($deployment->get('id'))->get('unmanaged_domains'));

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

        $output->newLine();
        $output->warning('Not all domains in this project are managed by Ymir. The following DNS record(s) need to be manually added to your DNS server for these domains to work:');
        $output->newLine();
        $output->table(
            ['Type', 'Name', 'Value'],
            $unmanagedDomains->map(function (string $unmanagedDomain) use ($vanityDomainName) {
                return ['CNAME', $unmanagedDomain, $vanityDomainName];
            })->all()
        );
    }

    /**
     * Create the deployment and return its ID.
     */
    abstract protected function createDeployment(InputInterface $input, OutputInterface $output): Collection;

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
