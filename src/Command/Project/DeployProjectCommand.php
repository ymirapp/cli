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
use Symfony\Component\Console\Input\InputOption;
use Tightenco\Collect\Support\Collection;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\Uploads\ImportUploadsCommand;
use Ymir\Cli\Console\OutputInterface;
use Ymir\Cli\ProjectConfiguration\ProjectConfiguration;

class DeployProjectCommand extends AbstractProjectDeploymentCommand
{
    /**
     * The alias of the command.
     *
     * @var string
     */
    public const ALIAS = 'deploy';

    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'project:deploy';

    /**
     * The build "uploads" directory.
     *
     * @var string
     */
    private $uploadsDirectory;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, CliConfiguration $cliConfiguration, ProjectConfiguration $projectConfiguration, string $uploadsDirectory, array $deploymentSteps = [])
    {
        parent::__construct($apiClient, $cliConfiguration, $projectConfiguration, $deploymentSteps);

        $this->uploadsDirectory = $uploadsDirectory;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Deploy project to an environment')
            ->setAliases([self::ALIAS])
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to deploy to', 'staging')
            ->addOption('with-uploads', null, InputOption::VALUE_NONE, 'Import the "uploads" directory during the deployment');
    }

    /**
     * {@inheritdoc}
     */
    protected function createDeployment(InputInterface $input, OutputInterface $output): Collection
    {
        $environment = $this->getStringArgument($input, 'environment');
        $projectId = $this->projectConfiguration->getProjectId();
        $withUploadsOption = $this->getBooleanOption($input, 'with-uploads');

        $this->invoke($output, ValidateProjectCommand::NAME, ['environments' => $environment]);
        $this->invoke($output, BuildProjectCommand::NAME, array_merge(['environment' => $environment], $withUploadsOption ? ['--with-uploads' => null] : []));

        if ($withUploadsOption) {
            $this->invoke($output, ImportUploadsCommand::NAME, ['path' => $this->uploadsDirectory, '--environment' => $environment, '--force' => null]);
        }

        $deployment = $this->apiClient->createDeployment($projectId, $environment, $this->projectConfiguration);

        if (!$deployment->has('id')) {
            throw new RuntimeException('There was an error creating the deployment');
        }

        return $deployment;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSuccessMessage(string $environment): string
    {
        return sprintf('Project deployed successfully to "<comment>%s</comment>" environment', $environment);
    }
}
