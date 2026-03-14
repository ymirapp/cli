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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\ApiClient;
use Ymir\Cli\Command\AbstractCommand;
use Ymir\Cli\Command\LocalProjectCommandInterface;
use Ymir\Cli\Dockerfile;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\ExecutionContextFactory;
use Ymir\Cli\Project\Configuration\ImageDeploymentConfigurationChange;

class CreateDockerfileCommand extends AbstractCommand implements LocalProjectCommandInterface
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'docker:create';

    /**
     * Default architecture used when none is configured.
     */
    private const DEFAULT_ARCHITECTURE = 'arm64';

    /**
     * The project Dockerfile.
     *
     * @var Dockerfile
     */
    private $dockerfile;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, ExecutionContextFactory $contextFactory, Dockerfile $dockerfile)
    {
        parent::__construct($apiClient, $contextFactory);

        $this->dockerfile = $dockerfile;
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
            ->addOption('architecture', null, InputOption::VALUE_REQUIRED, 'Docker architecture (arm64 or x86_64)')
            ->addOption('php', null, InputOption::VALUE_REQUIRED, 'PHP version tag or version (for example: php-83 or 8.3)')
            ->addOption('configure-project', null, InputOption::VALUE_NONE, 'Configure project\'s ymir.yml file');
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $environment = $this->input->getStringArgument('environment', false);

        if (!empty($environment) && !$this->getProjectConfiguration()->hasEnvironment($environment)) {
            throw new InvalidInputException(sprintf('Environment "%s" not found in ymir.yml file', $environment));
        }

        if (!$this->dockerfile->exists($environment) || $this->output->confirm('Dockerfile already exists. Do you want to overwrite it?', false)) {
            $architecture = $this->resolveArchitecture($environment);
            $phpVersion = $this->resolvePhpVersion($environment);

            $this->dockerfile->create($architecture, $phpVersion, $environment);

            $this->output->info($this->generateDockerfileCreatedMessage($architecture, $environment, $phpVersion));
        }

        if (!$this->input->getBooleanOption('configure-project') && !$this->output->confirm('Would you also like to configure your project for container image deployment?')) {
            return;
        }

        $configurationChange = new ImageDeploymentConfigurationChange();

        if (empty($environment)) {
            $this->getProjectConfiguration()->applyChangesToEnvironments($configurationChange);

            return;
        }

        $this->getProjectConfiguration()->applyChangesToEnvironment($environment, $configurationChange);
    }

    /**
     * Generate the success message after creating the Dockerfile.
     */
    private function generateDockerfileCreatedMessage(string $architecture, string $environment, string $phpVersion): string
    {
        return sprintf('Created <comment>%s</comment> for PHP <comment>%s</comment> and <comment>%s</comment> architecture', Dockerfile::getFileName($environment), $phpVersion, $architecture);
    }

    /**
     * Resolve the architecture used to generate the Dockerfile.
     */
    private function resolveArchitecture(string $environment): string
    {
        $architecture = (string) $this->input->getStringOption('architecture');

        if (empty($architecture) && !empty($environment)) {
            $architecture = $this->getProjectConfiguration()->getEnvironmentConfiguration($environment)->getArchitecture();
        } elseif (empty($architecture)) {
            $architecture = self::DEFAULT_ARCHITECTURE;
        }

        if (!in_array($architecture, ['arm64', 'x86_64'], true)) {
            throw new InvalidInputException(sprintf('Invalid architecture "%s". Supported values are: arm64, x86_64', $architecture));
        }

        return $architecture;
    }

    /**
     * Resolve the PHP version used to generate the Dockerfile.
     */
    private function resolvePhpVersion(string $environment): string
    {
        $phpVersion = (string) $this->input->getStringOption('php');

        if (empty($phpVersion) && !empty($environment)) {
            $phpVersion = $this->getProjectConfiguration()->getEnvironmentConfiguration($environment)->getPhpVersion();
        } elseif (empty($phpVersion)) {
            $phpVersion = $this->getProjectConfiguration()->getProjectType()->getDefaultPhpVersion();
        }

        return $phpVersion;
    }
}
