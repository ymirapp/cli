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

namespace Ymir\Cli\Command\Uploads;

use Illuminate\Support\Enumerable;
use Illuminate\Support\LazyCollection;
use League\Flysystem\CorruptedPathDetected;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\StorageAttributes;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Exception\InvalidInputException;
use Ymir\Cli\FileUploader;
use Ymir\Cli\Project\Configuration\ProjectConfiguration;
use Ymir\Cli\Project\Type\AbstractWordPressProjectType;

class ImportUploadsCommand extends AbstractProjectCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'uploads:import';

    /**
     * The uploader used to upload files.
     *
     * @var FileUploader
     */
    private $uploader;

    /**
     * Constructor.
     */
    public function __construct(ApiClient $apiClient, CliConfiguration $cliConfiguration, ProjectConfiguration $projectConfiguration, FileUploader $uploader)
    {
        parent::__construct($apiClient, $cliConfiguration, $projectConfiguration);

        $this->uploader = $uploader;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Import files to the environment "uploads" directory')
            ->addArgument('path', InputArgument::OPTIONAL, 'The path to the files to import')
            ->addOption('environment', null, InputOption::VALUE_REQUIRED, 'The environment to upload files to', 'staging')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force the import to run')
            ->addOption('size', null, InputOption::VALUE_REQUIRED, 'The number of files to process at a time', 1000);
    }

    /**
     * {@inheritdoc}
     */
    protected function perform()
    {
        $path = $this->input->getStringArgument('path');
        $projectType = $this->projectConfiguration->getProjectType();

        if (!$projectType instanceof AbstractWordPressProjectType) {
            throw new RuntimeException('You can only use this command with WordPress projects');
        }

        if (empty($path)) {
            $path = $projectType->getUploadsDirectoryPath($path);
        }

        $adapter = $this->getAdapter($path);
        $environment = (string) $this->input->getStringOption('environment');
        $filesystem = new Filesystem($adapter);
        $size = $this->input->getNumericOption('size');

        if ($size < 1) {
            throw new InvalidInputException('Cannot have a "size" smaller than 1');
        }

        if (!$this->input->getBooleanOption('force') && !$this->output->confirm('Importing files will overwrite any existing file in the environment "uploads" directory. Do you want to proceed?')) {
            return;
        }

        $this->output->info(sprintf('Starting file import to the "<comment>%s</comment>" environment "uploads" directory', $environment));

        $progressBar = new ProgressBar($this->output);
        $progressBar->setFormat("Importing file (<comment>%filename%</comment>)\nTotal files imported: <comment>%total%</comment>\n");
        $progressBar->setMessage('0', 'total');

        $corruptedFilePaths = [];
        $total = 0;

        if (!$adapter instanceof LocalFilesystemAdapter) {
            $this->output->infoWithWarning('Scanning remote "uploads" directory', 'takes a few seconds');
        }

        $requests = LazyCollection::make(function () use ($filesystem) {
            $files = $filesystem->listContents('', Filesystem::LIST_DEEP)->filter(function (StorageAttributes $attributes) {
                return $attributes->isFile();
            });

            foreach ($files as $file) {
                yield $file->path();
            }
        })->chunk($size)->mapWithKeys(function (Enumerable $chunkedFiles) use ($environment) {
            return $this->getSignedUploadRequest($environment, $chunkedFiles);
        })->map(function (array $request, string $filePath) use (&$corruptedFilePaths, $filesystem, $progressBar, &$total) {
            try {
                $request['body'] = $filesystem->readStream(mb_convert_encoding($filePath, 'UTF-8'));

                ++$total;

                $progressBar->setMessage($filePath, 'filename');
                $progressBar->setMessage((string) $total, 'total');
                $progressBar->advance();

                return $request;
            } catch (CorruptedPathDetected $exception) {
                $corruptedFilePaths[] = $filePath;

                return null;
            }
        })->filter();

        $this->uploader->batch('PUT', $requests);

        $this->output->info(sprintf('Files imported successfully to the "<comment>%s</comment>" environment "uploads" directory', $environment));

        if (!empty($corruptedFilePaths)) {
            $this->output->warning('The following files were not imported because their paths are corrupted:');
            $this->output->list($corruptedFilePaths);
        }
    }

    /**
     * Get the filesystem adapter for the given path.
     */
    private function getAdapter(string $path): FilesystemAdapter
    {
        $parsedPath = parse_url($path);

        if (!is_array($parsedPath) || !isset($parsedPath['host'], $parsedPath['scheme']) || !in_array($parsedPath['scheme'], ['ftp', 'sftp'])) {
            return new LocalFilesystemAdapter($path);
        }

        $parsedPath['pass'] = $parsedPath['pass'] ?? null;
        $parsedPath['path'] = $parsedPath['path'] ?? '/';
        $parsedPath['user'] = $parsedPath['user'] ?? get_current_user();

        switch ($parsedPath['scheme']) {
            case 'ftp':
                return new FtpAdapter(
                    FtpConnectionOptions::fromArray([
                        'host' => $parsedPath['host'],
                        'root' => $parsedPath['path'],
                        'username' => $parsedPath['user'],
                        'password' => $parsedPath['pass'],
                        'port' => $parsedPath['port'] ?? 21,
                    ])
                );
            case 'sftp':
                return new SftpAdapter(
                    SftpConnectionProvider::fromArray([
                        'host' => $parsedPath['host'],
                        'username' => $parsedPath['user'],
                        'password' => $parsedPath['pass'],
                        'port' => $parsedPath['port'] ?? 22,
                        'useAgent' => !$parsedPath['pass'],
                    ]),
                    $parsedPath['path']
                );
            default:
                throw new RuntimeException('Unable to create a filesystem adapter');
        }
    }

    /**
     * Get the signed upload request for the given environment and path.
     */
    private function getSignedUploadRequest(string $environment, Enumerable $files): array
    {
        return $this->apiClient->getSignedUploadRequests($this->projectConfiguration->getProjectId(), $environment, $files->map(function (string $filePath) {
            return ['path' => mb_convert_encoding($filePath, 'UTF-8')];
        })->all())->all();
    }
}
