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

namespace Ymir\Cli\Deployment;

use Illuminate\Support\Collection;
use Symfony\Component\Console\Helper\ProgressBar;
use Ymir\Cli\ApiClient;
use Ymir\Cli\Console\Input;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Executable\DockerExecutable;
use Ymir\Cli\FileUploader;
use Ymir\Cli\Project\Configuration\ProjectConfiguration;
use Ymir\Cli\Support\Arr;

class UploadFunctionCodeStep implements DeploymentStepInterface
{
    /**
     * The API client that interacts with the Ymir API.
     *
     * @var ApiClient
     */
    private $apiClient;

    /**
     * The path to the build artifact.
     *
     * @var string
     */
    private $buildArtifactPath;

    /**
     * The build directory where the project files are.
     *
     * @var string
     */
    private $buildDirectory;

    /**
     * The Docker executable.
     *
     * @var DockerExecutable
     */
    private $dockerExecutable;

    /**
     * The Ymir project configuration.
     *
     * @var ProjectConfiguration
     */
    private $projectConfiguration;

    /**
     * The uploader used to upload all the build files.
     *
     * @var FileUploader
     */
    private $uploader;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, string $buildArtifactPath, string $buildDirectory, DockerExecutable $dockerExecutable, ProjectConfiguration $projectConfiguration, FileUploader $uploader)
    {
        $this->apiClient = $apiClient;
        $this->buildArtifactPath = $buildArtifactPath;
        $this->buildDirectory = rtrim($buildDirectory, '/');
        $this->dockerExecutable = $dockerExecutable;
        $this->projectConfiguration = $projectConfiguration;
        $this->uploader = $uploader;
    }

    /**
     * {@inheritdoc}
     */
    public function perform(Collection $deployment, string $environment, Input $input, Output $output)
    {
        $configuration = $deployment->get('configuration');
        $deploymentType = Arr::get($configuration, sprintf('environments.%s.deployment', $environment), 'zip');

        if ('image' === $deploymentType) {
            $this->pushImage($deployment, $environment, $output);
        } elseif ('zip' === $deploymentType) {
            $this->uploadArtifact($deployment, $output);
        }
    }

    /**
     * Push image to deploy to project container repository.
     */
    private function pushImage(Collection $deployment, string $environment, Output $output)
    {
        $image = $this->apiClient->getDeploymentImage((int) $deployment->get('id'));
        $imageUri = (string) $image->get('image_uri');

        list($user, $password) = explode(':', base64_decode((string) $image->get('authorization_token')));

        $output->infoWithDelayWarning('Pushing container image');

        $this->dockerExecutable->login($user, $password, Arr::get((array) explode('/', $imageUri), 0), $this->buildDirectory);
        $this->dockerExecutable->tag(sprintf('%s:%s', $this->projectConfiguration->getProjectName(), $environment), $imageUri, $this->buildDirectory);
        $this->dockerExecutable->push($imageUri, $this->buildDirectory);
    }

    /**
     * Upload the build artifact to deploy.
     */
    private function uploadArtifact(Collection $deployment, Output $output)
    {
        $progressBar = new ProgressBar($output);

        $progressBar->setFormat('<info>%message%</info> (<comment>%percent%%</comment>)');
        $progressBar->setMessage('Uploading build');

        $this->uploader->uploadFile($this->buildArtifactPath, $this->apiClient->getArtifactUploadUrl((int) $deployment->get('id')), [], $progressBar);

        $output->newLine();
    }
}
