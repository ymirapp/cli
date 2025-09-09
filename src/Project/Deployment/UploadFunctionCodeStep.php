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

namespace Ymir\Cli\Project\Deployment;

use Symfony\Component\Console\Helper\ProgressBar;
use Ymir\Cli\Exception\ConfigurationException;
use Ymir\Cli\Exception\LogicException;
use Ymir\Cli\Exception\SystemException;
use Ymir\Cli\Executable\DockerExecutable;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\FileUploader;
use Ymir\Cli\Resource\Model\Deployment;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\Model\Project;
use Ymir\Cli\Support\Arr;

class UploadFunctionCodeStep implements DeploymentStepInterface
{
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
     * The uploader used to upload all the build files.
     *
     * @var FileUploader
     */
    private $uploader;

    /**
     * Constructor.
     */
    public function __construct(string $buildArtifactPath, string $buildDirectory, DockerExecutable $dockerExecutable, FileUploader $uploader)
    {
        $this->buildArtifactPath = $buildArtifactPath;
        $this->buildDirectory = rtrim($buildDirectory, '/');
        $this->dockerExecutable = $dockerExecutable;
        $this->uploader = $uploader;
    }

    /**
     * {@inheritdoc}
     */
    public function perform(ExecutionContext $context, Deployment $deployment, Environment $environment): void
    {
        $configuration = $deployment->getConfiguration();
        $deploymentType = Arr::get($configuration, sprintf('environments.%s.deployment.type', $environment->getName()));

        if (!in_array($deploymentType, ['image', 'zip'])) {
            throw new ConfigurationException(sprintf('Unsupported deployment type "%s" for environment "%s". Valid deployment types are: "image", "zip"', $deploymentType ?? 'null', $environment->getName()));
        }

        if ('image' === $deploymentType) {
            $this->pushImage($context, $deployment, $environment->getName());
        } elseif ('zip' === $deploymentType) {
            $this->uploadArtifact($context, $deployment);
        }
    }

    /**
     * Push image to deploy to project container repository.
     */
    private function pushImage(ExecutionContext $context, Deployment $deployment, string $environment): void
    {
        $project = $context->getProject();

        if (!$project instanceof Project) {
            throw new LogicException('No project found in the current context');
        }

        $image = $context->getApiClient()->getDeploymentImage($deployment);

        if (!$image->has(['authorization_token', 'image_uri'])) {
            throw new SystemException('Deployment image data is incomplete');
        }

        $decodedToken = base64_decode((string) $image->get('authorization_token'));
        $imageUri = (string) $image->get('image_uri');

        if (1 !== substr_count($decodedToken, ':')) {
            throw new SystemException('Invalid authorization token format');
        }

        [$user, $password] = explode(':', $decodedToken);

        $context->getOutput()->infoWithDelayWarning('Pushing container image');

        $this->dockerExecutable->login($user, $password, Arr::get(explode('/', $imageUri), 0), $this->buildDirectory);
        $this->dockerExecutable->tag(sprintf('%s:%s', $project->getName(), $environment), $imageUri, $this->buildDirectory);
        $this->dockerExecutable->push($imageUri, $this->buildDirectory);
    }

    /**
     * Upload the build artifact to deploy.
     */
    private function uploadArtifact(ExecutionContext $context, Deployment $deployment): void
    {
        $output = $context->getOutput();
        $progressBar = new ProgressBar($output);

        $progressBar->setFormat('<info>%message%</info> (<comment>%percent%%</comment>)');
        $progressBar->setMessage('Uploading build');

        $this->uploader->uploadFile($this->buildArtifactPath, $context->getApiClient()->getArtifactUploadUrl($deployment), [], $progressBar);

        $output->newLine();
    }
}
