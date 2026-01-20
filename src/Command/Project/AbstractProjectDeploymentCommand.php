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

use Ymir\Cli\ApiClient;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Command\Email\CreateEmailIdentityCommand;
use Ymir\Cli\Command\Environment\GetEnvironmentUrlCommand;
use Ymir\Cli\Command\LocalProjectCommandInterface;
use Ymir\Cli\ExecutionContextFactory;
use Ymir\Cli\Project\Deployment\DeploymentStepInterface;
use Ymir\Cli\Resource\Model\Deployment;
use Ymir\Cli\Resource\Model\Environment;

abstract class AbstractProjectDeploymentCommand extends AbstractCommand implements LocalProjectCommandInterface
{
    /**
     * The deployment steps to perform.
     *
     * @var DeploymentStepInterface[]
     */
    private $deploymentSteps;

    /**
     * The environment being deployed to.
     *
     * @var Environment|null
     */
    private $environment;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, ExecutionContextFactory $contextFactory, array $deploymentSteps = [])
    {
        parent::__construct($apiClient, $contextFactory);

        $this->deploymentSteps = [];

        foreach ($deploymentSteps as $deploymentStep) {
            $this->addDeploymentStep($deploymentStep);
        }
    }

    /**
     * Get the environment being deployed to.
     */
    protected function getEnvironment(): Environment
    {
        return $this->environment;
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $this->environment = $this->resolve(Environment::class, $this->getEnvironmentQuestion());

        $deployment = $this->createDeployment();

        foreach ($this->deploymentSteps as $deploymentStep) {
            $deploymentStep->perform($this->getContext(), $deployment, $this->environment);
        }

        $this->output->info($this->getSuccessMessage($this->environment->getName()));

        $this->invoke(GetEnvironmentUrlCommand::NAME, ['environment' => $this->environment->getName()]);

        if (!$this->getProjectConfiguration()->getEnvironmentConfiguration($this->environment->getName())->hasDomainConfiguration()) {
            $this->output->newLine();
            $this->output->note(sprintf('You cannot send emails using the "<comment>ymirsites.com</comment>" domain. Please use the "<comment>%s</comment>" command to add an email address or domain for sending emails.', CreateEmailIdentityCommand::NAME));

            $this->output->newLine();
            $this->output->writeln("This environment isn't mapped to a domain.\n\nPlease refer to this guide to learn how: https://docs.ymirapp.com/guides/domain-mapping.html");

            return;
        }

        $unmanagedDomains = collect($this->apiClient->getDeployment($deployment->getId())->getUnmanagedDomains());

        if ($unmanagedDomains->isEmpty()) {
            return;
        }

        $vanityDomainName = $this->environment->getVanityDomainName();

        $unmanagedDomains = $unmanagedDomains->filter(function (string $unmanagedDomain) use ($vanityDomainName): bool {
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
    abstract protected function createDeployment(): Deployment;

    /**
     * Get the question to ask for the environment.
     */
    abstract protected function getEnvironmentQuestion(): string;

    /**
     * Get the message to display when a deployment was successful.
     */
    abstract protected function getSuccessMessage(string $environment): string;

    /**
     * Add a deployment step to the command.
     */
    private function addDeploymentStep(DeploymentStepInterface $deploymentStep): void
    {
        $this->deploymentSteps[] = $deploymentStep;
    }
}
