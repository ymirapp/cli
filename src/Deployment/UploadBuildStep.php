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
use Ymir\Cli\Console\ConsoleOutput;
use Ymir\Cli\FileUploader;

class UploadBuildStep implements DeploymentStepInterface
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
     * The uploader used to upload all the build files.
     *
     * @var FileUploader
     */
    private $uploader;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, string $buildArtifactPath, FileUploader $uploader)
    {
        $this->apiClient = $apiClient;
        $this->buildArtifactPath = $buildArtifactPath;
        $this->uploader = $uploader;
    }

    /**
     * {@inheritdoc}
     */
    public function perform(Collection $deployment, ConsoleOutput $output)
    {
        $progressBar = new ProgressBar($output);

        $progressBar->setFormat('<info>%message%</info> (<comment>%percent%%</comment>)');
        $progressBar->setMessage('Uploading build');

        $this->uploader->uploadFile($this->buildArtifactPath, $this->apiClient->getArtifactUploadUrl($deployment->get('id')), [], $progressBar);

        $output->newLine();
    }
}
