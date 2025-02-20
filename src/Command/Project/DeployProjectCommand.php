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
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\Uploads\ImportUploadsCommand;
use Ymir\Cli\Project\Configuration\ProjectConfiguration;

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
     * The build "uploads" directory.
     *
     * @var string
     */
    private $uploadsDirectory;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, string $assetsDirectory, CliConfiguration $cliConfiguration, ProjectConfiguration $projectConfiguration, string $uploadsDirectory, array $deploymentSteps = [])
    {
        parent::__construct($apiClient, $cliConfiguration, $projectConfiguration, $deploymentSteps);

        $this->assetsDirectory = $assetsDirectory;
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
            ->addOption('debug-build', null, InputOption::VALUE_NONE, 'Run the deployment build in debug mode')
            ->addOption('force-assets', null, InputOption::VALUE_NONE, 'Force processing assets')
            ->addOption('with-uploads', null, InputOption::VALUE_NONE, 'Import the "uploads" directory during the deployment');
    }

    /**
     * {@inheritdoc}
     */
    protected function createDeployment(): Collection
    {
        $environment = $this->input->getStringArgument('environment');
        $projectId = $this->projectConfiguration->getProjectId();
        $withUploadsOption = $this->input->getBooleanOption('with-uploads');

        $this->invoke(ValidateProjectCommand::NAME, ['environments' => $environment]);
        $this->invoke(BuildProjectCommand::NAME, array_merge(['environment' => $environment], $this->input->getBooleanOption('debug-build') ? ['--debug' => null] : [], $withUploadsOption ? ['--with-uploads' => null] : []));

        if ($withUploadsOption) {
            $this->invoke(ImportUploadsCommand::NAME, ['path' => $this->uploadsDirectory, '--environment' => $environment, '--force' => null]);
        }

        $deployment = $this->apiClient->createDeployment($projectId, $environment, $this->projectConfiguration, $this->generateDirectoryHash($this->assetsDirectory));

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

    /**
     * Generate a hash for the content of the given directory.
     */
    private function generateDirectoryHash(string $directory): string
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory), \RecursiveIteratorIterator::SELF_FIRST);

        return hash('sha256', collect($iterator)->filter(function (\SplFileInfo $file) {
            return $file->isFile();
        })->mapWithKeys(function (\SplFileInfo $file) {
            return [substr($file->getRealPath(), (int) strrpos($file->getRealPath(), '.ymir')) => $file->getRealPath()];
        })->map(function (string $realPath, string $relativePath) {
            return sprintf('%s|%s', $relativePath, hash_file('sha256', $realPath));
        })->implode(''));
    }
}
