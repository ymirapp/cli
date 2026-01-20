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

use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Finder\Finder;
use Ymir\Cli\Console\Output;
use Ymir\Cli\Exception\LogicException;
use Ymir\Cli\ExecutionContext;
use Ymir\Cli\FileUploader;
use Ymir\Cli\Resource\Model\Deployment;
use Ymir\Cli\Resource\Model\Environment;
use Ymir\Cli\Resource\Model\Project;

class ProcessAssetsStep implements DeploymentStepInterface
{
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
    public function __construct(string $assetsDirectory, FileUploader $uploader)
    {
        $this->assetsDirectory = $assetsDirectory;
        $this->uploader = $uploader;
    }

    /**
     * {@inheritdoc}
     */
    public function perform(ExecutionContext $context, Deployment $deployment, Environment $environment): void
    {
        $project = $context->getProject();

        if (!$project instanceof Project) {
            throw new LogicException('No project found in the current context');
        }

        $apiClient = $context->getApiClient();
        $deploymentWithAssetsHash = null;
        $output = $context->getOutput();

        if (!$context->getInput()->getBooleanOption('force-assets')) {
            $deploymentWithAssetsHash = $apiClient->getDeployments($project, $environment)
                ->where('status', 'finished')
                ->firstWhere('assets_hash', $deployment->getAssetsHash());
        }

        if (null !== $deploymentWithAssetsHash) {
            $output->infoWithWarning('No assets change detected', 'skipping processing assets');

            return;
        }

        $output->info(sprintf('Processing <comment>%s</comment> assets', $project->getName()));

        $output->writeStep('Getting signed asset URLs');
        $assetFiles = $this->getAssetFiles();
        $signedAssetRequests = $apiClient->getSignedAssetRequests($deployment, $assetFiles->map(function (array $asset) {
            return [
                'path' => mb_convert_encoding($asset['relative_path'], 'UTF-8'),
                'hash' => $asset['hash'],
            ];
        })->all());
        $UnprocessedAssetFiles = count($assetFiles) - count($signedAssetRequests);

        if (0 !== $UnprocessedAssetFiles) {
            $output->warning(sprintf('Unable to process %s asset files', $UnprocessedAssetFiles));
        }

        $signedAssetRequests = $signedAssetRequests->groupBy('command', true);

        $this->copyAssetFiles($assetFiles->filter(function (array $asset) use ($signedAssetRequests): bool {
            return isset($signedAssetRequests['copy'][$asset['relative_path']]);
        })->map(function (array $asset) use ($signedAssetRequests): array {
            return $signedAssetRequests['copy'][$asset['relative_path']];
        }), $output);

        $this->uploadAssetFiles($assetFiles->filter(function (array $asset) use ($signedAssetRequests): bool {
            return isset($signedAssetRequests['store'][$asset['relative_path']]);
        })->mapWithKeys(function (array $asset) use ($signedAssetRequests): array {
            return [$asset['real_path'] => $signedAssetRequests['store'][$asset['relative_path']]];
        }), $output);
    }

    /**
     * Send the given asset file copy requests.
     */
    private function copyAssetFiles(Collection $requests, Output $output): void
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
    private function uploadAssetFiles(Collection $requests, Output $output): void
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
