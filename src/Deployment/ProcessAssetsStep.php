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
use Symfony\Component\Finder\Finder;
use Tightenco\Collect\Support\Collection;
use Ymir\Cli\ApiClient;
use Ymir\Cli\Console\ConsoleOutput;
use Ymir\Cli\FileUploader;

class ProcessAssetsStep implements DeploymentStepInterface
{
    /**
     * The API client that interacts with the Ymir API.
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
    public function perform(Collection $deployment, ConsoleOutput $output)
    {
        $output->info('Processing assets');

        $output->writeStep('Getting signed asset URLs');
        $assetFiles = $this->getAssetFiles();
        $signedAssetRequests = $assetFiles->map(function (array $asset) {
            return [
                'path' => $asset['relative_path'],
                'hash' => $asset['hash'],
            ];
        })->chunk(500)->flatMap(function (Collection $chunkedAssetFiles) use ($deployment) {
            return $this->apiClient->getSignedAssetRequests($deployment->get('id'), $chunkedAssetFiles->all());
        });

        if (count($assetFiles) !== count($signedAssetRequests)) {
            $output->warn('Warning: Not all asset files were processed successfully');
        }

        $signedAssetRequests = $signedAssetRequests->groupBy('command', true);

        $this->copyAssetFiles($assetFiles->filter(function (array $asset) use ($signedAssetRequests) {
            return isset($signedAssetRequests['copy'][$asset['relative_path']]);
        })->map(function (array $asset) use ($signedAssetRequests) {
            return $signedAssetRequests['copy'][$asset['relative_path']];
        })->all(), $output);

        $this->uploadAssetFiles($assetFiles->filter(function (array $asset) use ($signedAssetRequests) {
            return isset($signedAssetRequests['store'][$asset['relative_path']]);
        })->mapWithKeys(function (array $asset) use ($signedAssetRequests) {
            return [$asset['real_path'] => $signedAssetRequests['store'][$asset['relative_path']]];
        })->all(), $output);
    }

    /**
     * Send the given asset file copy requests.
     */
    private function copyAssetFiles(array $requests, ConsoleOutput $output)
    {
        if (empty($requests)) {
            return;
        }

        $progressBar = new ProgressBar($output);
        $progressBar->setFormat('  > Copying unchanged asset files (<comment>%current%/%max%</comment>)');

        $this->uploader->batch('PUT', $requests, $progressBar);

        $output->newLine();
    }

    /**
     * Get all the asset files.
     */
    private function getAssetFiles(): Collection
    {
        $assetFiles = [];
        $finder = Finder::create()
            ->in($this->assetsDirectory)
            ->files();

        foreach ($finder as $assetFile) {
            $assetFiles[] = [
                'real_path' => $assetFile->getRealPath(),
                'relative_path' => str_replace('\\', '/', $assetFile->getRelativePathname()),
                'hash' => md5_file((string) $assetFile->getRealPath()),
            ];
        }

        return collect($assetFiles);
    }

    /**
     * Send the given asset file upload requests.
     */
    private function uploadAssetFiles(array $requests, ConsoleOutput $output)
    {
        if (empty($requests)) {
            return;
        }

        $progressBar = new ProgressBar($output);
        $progressBar->setFormat('  > Uploading new asset files (<comment>%current%/%max%</comment>)');
        $progressBar->start(count($requests));

        foreach ($requests as $realFilePath => $request) {
            $this->uploader->uploadFile((string) $realFilePath, (string) $request['uri'], $request['headers']);
            $progressBar->advance();
        }

        $output->newLine();
    }
}
