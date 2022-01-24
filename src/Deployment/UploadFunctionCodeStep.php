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

use Symfony\Component\Console\Helper\ProgressBar;
use Tightenco\Collect\Support\Collection;
use Ymir\Cli\ApiClient;
use Ymir\Cli\Console\OutputInterface;
use Ymir\Cli\FileUploader;
use Ymir\Cli\ProjectConfiguration\ProjectConfiguration;
use Ymir\Cli\Support\Arr;
use Ymir\Cli\Tool\Docker;

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
    public function __construct(ApiClient $apiClient, string $buildArtifactPath, string $buildDirectory, ProjectConfiguration $projectConfiguration, FileUploader $uploader)
    {
        $this->apiClient = $apiClient;
        $this->buildArtifactPath = $buildArtifactPath;
        $this->buildDirectory = rtrim($buildDirectory, '/');
        $this->projectConfiguration = $projectConfiguration;
        $this->uploader = $uploader;
    }

    /**
     * {@inheritdoc}
     */
    public function perform(Collection $deployment, OutputInterface $output)
    {
        $configuration = $deployment->get('configuration');
        $environment = Arr::first(array_keys($configuration['environments']));
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
    private function pushImage(Collection $deployment, string $environment, OutputInterface $output)
    {
        $image = $this->apiClient->getDeploymentImage($deployment->get('id'));
        $imageUri = $image->get('image_uri');

        list($user, $password) = explode(':', base64_decode($image->get('authorization_token')));

        $output->infoWithDelayWarning('Pushing container image');

        Docker::login($user, $password, Arr::get((array) explode('/', $imageUri), 0), $this->buildDirectory);
        Docker::tag(sprintf('%s:%s', $this->projectConfiguration->getProjectName(), $environment), $imageUri, $this->buildDirectory);
        Docker::push($imageUri, $this->buildDirectory);
    }

    /**
     * Upload the build artifact to deploy.
     */
    private function uploadArtifact(Collection $deployment, OutputInterface $output)
    {
        $progressBar = new ProgressBar($output);

        $progressBar->setFormat('<info>%message%</info> (<comment>%percent%%</comment>)');
        $progressBar->setMessage('Uploading build');

        $this->uploader->uploadFile($this->buildArtifactPath, $this->apiClient->getArtifactUploadUrl($deployment->get('id')), [], $progressBar);

        $output->newLine();
    }
}
