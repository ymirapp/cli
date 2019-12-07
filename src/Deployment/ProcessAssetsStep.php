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
use Symfony\Component\Finder\Finder;

class ProcessAssetsStep implements DeploymentStepInterface
{
    /**
     * The API client that interacts with the placeholder API.
     *
     * @var ApiClient
     */
    private $apiClient;

    /**
     * The assets directory where the asset files were copied to.
     *
     * @var string
     */
    private $assetsDirectory;

    /**
     * The uploader used to upload all the build files.
     *
     * @var FileUploader
     */
    private $uploader;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, string $assetsDirectory, FileUploader $uploader)
    {
        $this->apiClient = $apiClient;
        $this->assetsDirectory = $assetsDirectory;
        $this->uploader = $uploader;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Uploading build';
    }

    /**
     * {@inheritdoc}
     */
    public function perform(int $deploymentId, OutputStyle $output)
    {
        $output->info('Processing assets');

        $assetFiles = $this->getAssetFiles();
        $assetUploadUrls = $this->apiClient->getAssetUploadUrls($deploymentId, array_keys($assetFiles));

        if (!empty(array_diff_key($assetFiles, $assetUploadUrls))) {
            $output->warn('Not all asset files were processed successfully');
        }

        $progressBar = new ProgressBar($output);
        $progressBar->setFormat('<info>%message%</info> (<comment>%current%/%max%</comment>)');
        $progressBar->setMessage('Uploading assets');
        $progressBar->setMaxSteps(count($assetUploadUrls));
        $progressBar->start();

        foreach ($assetUploadUrls as $relativeFilePath => $uploadUrl) {
            $this->uploader->uploadFile($assetFiles[$relativeFilePath], $uploadUrl, ['Cache-Control' => 'public, max-age=2628000']);
            $progressBar->advance();
        }

        $output->newLine();
    }

    /**
     * Get all the asset files as an associative array with the relative path as the key
     * and real path as the value.
     */
    private function getAssetFiles(): array
    {
        $assetFiles = [];
        $finder = Finder::create()
            ->in($this->assetsDirectory)
            ->files();

        foreach ($finder as $assetFile) {
            $assetFiles[$assetFile->getRelativePathname()] = $assetFile->getRealPath();
        }

        return $assetFiles;
    }
}
