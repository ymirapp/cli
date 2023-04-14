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
use Illuminate\Support\LazyCollection;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Finder\Finder;
use Ymir\Cli\ApiClient;
use Ymir\Cli\Console\OutputInterface;
use Ymir\Cli\FileUploader;
use Ymir\Cli\ProjectConfiguration\ProjectConfiguration;

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
    public function __construct(ApiClient $apiClient, string $assetsDirectory, ProjectConfiguration $projectConfiguration, FileUploader $uploader)
    {
        $this->apiClient = $apiClient;
        $this->assetsDirectory = $assetsDirectory;
        $this->projectConfiguration = $projectConfiguration;
        $this->uploader = $uploader;
    }

    /**
     * {@inheritdoc}
     */
    public function perform(Collection $deployment, string $environment, OutputInterface $output)
    {
        $deploymentWithAssetsHash = $this->apiClient->getDeployments($this->projectConfiguration->getProjectId(), $environment)
            ->where('status', 'finished')
            ->firstWhere('assets_hash', $deployment->get('assets_hash'));

        if (null !== $deploymentWithAssetsHash) {
            $output->infoWithWarning('No assets change detected', 'skipping processing assets');

            return;
        }

        $output->info('Processing assets');

        $output->writeStep('Getting signed asset URLs');
        $assetFiles = $this->getAssetFiles();
        $signedAssetRequests = $this->apiClient->getSignedAssetRequests($deployment->get('id'), $assetFiles->map(function (array $asset) {
            return [
                'path' => utf8_encode($asset['relative_path']),
                'hash' => $asset['hash'],
            ];
        })->all());

        if (count($assetFiles) !== count($signedAssetRequests)) {
            $output->warning('Not all asset files were processed successfully');
        }

        $signedAssetRequests = $signedAssetRequests->groupBy('command', true);

        $this->copyAssetFiles($assetFiles->filter(function (array $asset) use ($signedAssetRequests) {
            return isset($signedAssetRequests['copy'][$asset['relative_path']]);
        })->map(function (array $asset) use ($signedAssetRequests) {
            return $signedAssetRequests['copy'][$asset['relative_path']];
        }), $output);

        $this->uploadAssetFiles($assetFiles->filter(function (array $asset) use ($signedAssetRequests) {
            return isset($signedAssetRequests['store'][$asset['relative_path']]);
        })->mapWithKeys(function (array $asset) use ($signedAssetRequests) {
            return [$asset['real_path'] => $signedAssetRequests['store'][$asset['relative_path']]];
        }), $output);
    }

    /**
     * Send the given asset file copy requests.
     */
    private function copyAssetFiles(Collection $requests, OutputInterface $output)
    {
        if ($requests->isEmpty()) {
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
    private function uploadAssetFiles(Collection $requests, OutputInterface $output)
    {
        if ($requests->isEmpty()) {
            return;
        }

        $progressBar = new ProgressBar($output);
        $progressBar->setFormat('  > Uploading new asset files (<comment>%current%/%max%</comment>)');

        $requests = LazyCollection::make($requests)->map(function (array $request, string $realFilePath) {
            $request['body'] = fopen($realFilePath, 'r+');

            return $request;
        });

        $this->uploader->batch('PUT', $requests, $progressBar);

        $output->newLine();
    }
}
