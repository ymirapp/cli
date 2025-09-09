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
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\ApiClient;
use Ymir\Cli\Command\Media\ImportMediaCommand;
use Ymir\Cli\Exception\Project\DeploymentFailedException;
use Ymir\Cli\Exception\Project\UnsupportedProjectException;
use Ymir\Cli\ExecutionContextFactory;
use Ymir\Cli\Project\Type\SupportsMediaInterface;
use Ymir\Cli\Resource\Model\Deployment;

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
     * The assets directory where the asset files were copied to.
     *
     * @var string
     */
    private $assetsDirectory;

    /**
     * The build media directory.
     *
     * @var string
     */
    private $mediaDirectory;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, string $assetsDirectory, ExecutionContextFactory $contextFactory, string $mediaDirectory, array $deploymentSteps = [])
    {
        parent::__construct($apiClient, $contextFactory, $deploymentSteps);

        $this->assetsDirectory = $assetsDirectory;
        $this->mediaDirectory = $mediaDirectory;
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
            ->addArgument('environment', InputArgument::OPTIONAL, 'The name of the environment to deploy to')
            ->addOption('debug-build', null, InputOption::VALUE_NONE, 'Run the deployment build in debug mode')
            ->addOption('force-assets', null, InputOption::VALUE_NONE, 'Force processing assets')
            ->addOption('with-media', null, InputOption::VALUE_NONE, 'Import the media directory during the deployment');
    }

    /**
     * {@inheritdoc}
     */
    protected function createDeployment(): Deployment
    {
        $projectType = $this->getProjectConfiguration()->getProjectType();
        $withMediaOption = $this->input->getBooleanOption('with-media');

        if ($withMediaOption && !$projectType instanceof SupportsMediaInterface) {
            throw new UnsupportedProjectException('This project type doesn\'t support media operations');
        }

        $environment = $this->getEnvironment()->getName();

        $this->invoke(ValidateProjectCommand::NAME, ['environments' => [$environment]]);
        $this->invoke(BuildProjectCommand::NAME, array_merge(['environment' => $environment], $this->input->getBooleanOption('debug-build') ? ['--debug' => null] : [], $withMediaOption ? ['--with-media' => null] : []));

        if ($withMediaOption) {
            $this->invoke(ImportMediaCommand::NAME, ['path' => $this->mediaDirectory, '--environment' => $environment, '--force' => null]);
        }

        $deployment = $this->apiClient->createDeployment($this->getProject(), $this->getEnvironment(), $this->getProjectConfiguration()->toArray(), $this->generateDirectoryHash($this->assetsDirectory));

        if (!$deployment->getId()) {
            throw new DeploymentFailedException('There was an error creating the deployment');
        }

        return $deployment;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEnvironmentQuestion(): string
    {
        return 'Which <comment>%s</comment> environment would you like to deploy to?';
    }

    /**
     * {@inheritdoc}
     */
    protected function getSuccessMessage(string $environment): string
    {
        return sprintf('Project deployed successfully to "<comment>%s</comment>" environment', $environment);
    }

    /**
     * Generate a hash for the content of the given directory.
     */
    private function generateDirectoryHash(string $directory): string
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory), \RecursiveIteratorIterator::SELF_FIRST);

        return hash('sha256', collect($iterator)->filter(function (\SplFileInfo $file): bool {
            return $file->isFile();
        })->mapWithKeys(function (\SplFileInfo $file): array {
            return [substr($file->getRealPath(), (int) strrpos($file->getRealPath(), '.ymir')) => $file->getRealPath()];
        })->map(function (string $realPath, string $relativePath): string {
            return sprintf('%s|%s', $relativePath, hash_file('sha256', $realPath));
        })->implode(''));
    }
}
