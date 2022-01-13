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

namespace Ymir\Cli\Command\Docker;

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Console\OutputInterface;
use Ymir\Cli\ProjectConfiguration\ImageDeploymentConfigurationChange;
use Ymir\Cli\ProjectConfiguration\ProjectConfiguration;

class CreateDockerfileCommand extends AbstractProjectCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'docker:create';

    /**
     * The file system.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * The project directory where the project files are copied from.
     *
     * @var string
     */
    private $projectDirectory;

    /**
     * The directory where the stub files are.
     *
     * @var string
     */
    private $stubDirectory;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, CliConfiguration $cliConfiguration, Filesystem $filesystem, ProjectConfiguration $projectConfiguration, string $projectDirectory, string $stubDirectory)
    {
        parent::__construct($apiClient, $cliConfiguration, $projectConfiguration);

        $this->filesystem = $filesystem;
        $this->projectDirectory = rtrim($projectDirectory, '/');
        $this->stubDirectory = rtrim($stubDirectory, '/');
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Create a new Dockerfile')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to create the Dockerfile for')
            ->addOption('configure-project', null, InputOption::VALUE_NONE, 'Configure project\'s ymir.yml file');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, OutputInterface $output)
    {
        $dockerfileStub = 'Dockerfile';
        $dockerfileStubPath = $this->stubDirectory.'/'.$dockerfileStub;
        $environment = $this->getStringArgument($input, 'environment', false);

        if (!empty($environment)) {
            $dockerfileStub = $environment.'.'.$dockerfileStub;
        }

        if (!$this->filesystem->exists($dockerfileStubPath)) {
            throw new RuntimeException(sprintf('Cannot find "%s" stub file', $dockerfileStub));
        }

        $dockerfilePath = $this->projectDirectory.'/'.$dockerfileStub;

        if ($this->filesystem->exists($dockerfilePath) && !$output->confirm('Dockerfile already exists. Do you want to overwrite it?', false)) {
            return;
        }

        $this->filesystem->copy($dockerfileStubPath, $dockerfilePath);

        $message = 'Dockerfile created';

        if (!empty($environment)) {
            $message .= sprintf(' for "<comment>%s</comment>" environment', $environment);
        }

        $output->info($message);

        if (!$this->getBooleanOption($input, 'configure-project') && !$output->confirm('Would you like to configure your project for container image deployment?')) {
            return;
        }

        $configurationChange = new ImageDeploymentConfigurationChange();

        empty($environment) ? $this->projectConfiguration->applyChangesToEnvironments($configurationChange) : $this->projectConfiguration->applyChangesToEnvironment($environment, $configurationChange);
    }
}
