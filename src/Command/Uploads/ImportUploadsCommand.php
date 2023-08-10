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
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\StorageAttributes;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Console\OutputInterface;
use Ymir\Cli\FileUploader;
use Ymir\Cli\ProjectConfiguration\ProjectConfiguration;

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
    protected function perform(InputInterface $input, OutputInterface $output)
    {
        $path = $this->getStringArgument($input, 'path');
        $projectType = $this->projectConfiguration->getProjectType();

        if (empty($path) && 'bedrock' === $projectType) {
            $path = 'web/app/uploads';
        } elseif (empty($path) && 'wordpress' === $projectType) {
            $path = 'wp-content/uploads';
        }

        $adapter = $this->getAdapter($path);
        $environment = (string) $this->getStringOption($input, 'environment');
        $filesystem = new Filesystem($adapter);
        $size = $this->getNumericOption($input, 'size');

        if ($size < 1) {
            throw new InvalidArgumentException('Cannot have a "size" smaller than 1');
        }

        if (!$this->getBooleanOption($input, 'force') && !$output->confirm('Importing files will overwrite any existing file in the environment "uploads" directory. Do you want to proceed?')) {
            return;
        }

        $output->info(sprintf('Starting file import to the "<comment>%s</comment>" environment "uploads" directory', $environment));

        $progressBar = new ProgressBar($output);
        $progressBar->setFormat("Importing file (<comment>%filename%</comment>)\nTotal files imported: <comment>%total%</comment>\n");

        $total = 0;
        $progressBar->setMessage((string) $total, 'total');

        if (!$adapter instanceof LocalFilesystemAdapter) {
            $output->infoWithWarning('Scanning remote "uploads" directory', 'takes a few seconds');
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
        })->map(function (array $request, string $filePath) use ($filesystem, $progressBar, &$total) {
            $request['body'] = $filesystem->readStream(mb_convert_encoding($filePath, 'UTF-8'));

            ++$total;

            $progressBar->setMessage($filePath, 'filename');
            $progressBar->setMessage((string) $total, 'total');
            $progressBar->advance();

            return $request;
        });

        $this->uploader->batch('PUT', $requests);

        $output->info(sprintf('Files imported successfully to the "<comment>%s</comment>" environment "uploads" directory', $environment));
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

        if (!array_key_exists('pass', $parsedPath)) {
            $parsedPath['pass'] = null;
        }

        if ('ftp' === $parsedPath['scheme']) {
            return new FtpAdapter(
                FtpConnectionOptions::fromArray([
                    'host' => $parsedPath['host'],
                    'root' => $parsedPath['path'] ?? '/',
                    'username' => $parsedPath['user'] ?? get_current_user(),
                    $parsedPath['pass'],
                    'port' => $parsedPath['port'] ?? 21,
                ])
            );
        } elseif ('sftp' === $parsedPath['scheme']) {
            return new SftpAdapter(
                new SftpConnectionProvider($parsedPath['host'], $parsedPath['user'] ?? get_current_user(), $parsedPath['pass'], null, null, $parsedPath['port'] ?? 22, !$parsedPath['pass']),
                $parsedPath['path'] ?? '/'
            );
        }

        throw new RuntimeException('Unable to create a filesystem adapter');
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
