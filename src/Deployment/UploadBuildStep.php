<?php

declare(strict_types=1);

/*
 * This file is part of Placeholder command-line tool.
 *
 * (c) Carl Alexander <contact@carlalexander.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Placeholder\Cli\Deployment;

use Placeholder\Cli\ApiClient;
use Placeholder\Cli\Console\OutputStyle;
use Placeholder\Cli\FileUploader;
use Symfony\Component\Console\Helper\ProgressBar;

class UploadBuildStep implements DeploymentStepInterface
{
    /**
     * The API client that interacts with the placeholder API.
     *
     * @var ApiClient
     */
    private $apiClient;

    /**
     * The path to the build artifact.
     *
     * @var string
     */
    private $buildFilePath;

    /**
     * The uploader used to upload all the build files.
     *
     * @var FileUploader
     */
    private $uploader;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, string $buildFilePath, FileUploader $uploader)
    {
        $this->apiClient = $apiClient;
        $this->buildFilePath = $buildFilePath;
        $this->uploader = $uploader;
    }

    /**
     * {@inheritdoc}
     */
    public function perform(int $deploymentId, OutputStyle $output)
    {
        $progressBar = new ProgressBar($output);

        $progressBar->setFormat('<info>%message%</info> (<comment>%percent%%</comment>)');
        $progressBar->setMessage('Uploading build');

        $this->uploader->uploadFile($this->buildFilePath, $this->apiClient->getArtifactUploadUrl($deploymentId), [], $progressBar);

        $output->newLine();
    }
}
