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

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\PhpseclibV2\SftpAdapter;
use League\Flysystem\PhpseclibV2\SftpConnectionProvider;
use League\Flysystem\StorageAttributes;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Tightenco\Collect\Support\Collection;
use Ymir\Cli\ApiClient;
use Ymir\Cli\CliConfiguration;
use Ymir\Cli\Command\AbstractProjectCommand;
use Ymir\Cli\Console\ConsoleOutput;
use Ymir\Cli\FileUploader;
use Ymir\Cli\ProjectConfiguration;

class ImportCommand extends AbstractProjectCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    public const NAME = 'uploads:import';

    /**
     * The temporary directory used for importing.
     *
     * @var string
     */
    private $tempDirectory;

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
     * Delete the temporary directory when we're done the execution.
     */
    public function __destruct()
    {
        if (!is_string($this->tempDirectory) || !is_dir($this->tempDirectory)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->tempDirectory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($files as $file) {
            $function = $file->isDir() ? 'rmdir' : 'unlink';
            $function($file->getRealPath());
        }

        rmdir($this->tempDirectory);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Import files to the environment uploads directory')
            ->addArgument('path', InputArgument::REQUIRED, 'The path to the files to import')
            ->addOption('environment', null, InputOption::VALUE_REQUIRED, 'The environment name', 'staging')
            ->addOption('size', null, InputOption::VALUE_REQUIRED, 'The number of files to process at a time', 20);
    }

    /**
     * {@inheritdoc}
     */
    protected function perform(InputInterface $input, ConsoleOutput $output)
    {
        $environment = (string) $this->getStringOption($input, 'environment');
        $filesystem = new Filesystem($this->getAdapter($this->getStringArgument($input, 'path')));
        $size = $this->getNumericOption($input, 'size');

        if (!$output->confirm('Importing files will overwrite any existing file in the environment uploads directory. Do you want to proceed?')) {
            return;
        }

        $this->tempDirectory = $this->createTempDirectory();

        $output->info(sprintf('Starting file import to "<comment>%s</comment>" environment', $environment));

        $progressBar = new ProgressBar($output);
        $progressBar->setFormat("Importing file (<comment>%filename%</comment>)\nTotal files imported: <comment>%total%</comment>");

        $total = 0;
        $progressBar->setMessage((string) $total, 'total');

        foreach ($this->getFilesToImport($filesystem, (int) $size) as $files) {
            $this->getSignedUploadRequest($environment, $files)->each(function (array $request, string $filePath) use ($filesystem, $progressBar, &$total) {
                $tempFilePath = $this->tempDirectory.'/'.basename($filePath);

                $progressBar->setMessage($filePath, 'filename');
                $progressBar->advance();

                file_put_contents($tempFilePath, $filesystem->readStream($filePath));

                $this->uploader->uploadFile($tempFilePath, $request['uri'], $request['headers']);

                unlink($tempFilePath);

                $progressBar->setMessage((string) $total++, 'total');
                $progressBar->advance();
            });
        }

        $output->info(sprintf('Files imported successfully to "<comment>%s</comment>" environment', $environment));
    }

    /**
     * Create a temporary directory to copy over files temporarily.
     */
    private function createTempDirectory(): string
    {
        $baseDirectory = sys_get_temp_dir().'/';
        $maxAttempts = 100;

        if (!is_dir($baseDirectory)) {
            throw new RuntimeException(sprintf('"%s" isn\'t a directory', $baseDirectory));
        } elseif (!is_writable($baseDirectory)) {
            throw new RuntimeException(sprintf('"%s" isn\'t writable', $baseDirectory));
        }

        $attempts = 0;
        do {
            ++$attempts;
            $tmpDirectory = sprintf('%s%s%s', $baseDirectory, 'ymir_', mt_rand(100000, mt_getrandmax()));
        } while (!mkdir($tmpDirectory) && $attempts < $maxAttempts);

        if (!is_dir($tmpDirectory)) {
            throw new RuntimeException('Failed to create a temporary directory');
        }

        return $tmpDirectory;
    }

    /**
     * Get the filesystem adapter for the given path.
     */
    private function getAdapter(string $path): FilesystemAdapter
    {
        $parsedPath = parse_url($path);

        if (!is_array($parsedPath) || !isset($parsedPath['host'], $parsedPath['scheme']) || !in_array($parsedPath['scheme'], ['ftp', 'sftp'])) {
            return new LocalFilesystemAdapter($path);
        } elseif ('ftp' === $parsedPath['scheme']) {
            return new FtpAdapter(
                FtpConnectionOptions::fromArray([
                    'host' => $parsedPath['host'],
                    'root' => $parsedPath['path'] ?? '/',
                    'username' => $parsedPath['user'] ?? get_current_user(),
                    $parsedPath['pass'] ?? null,
                    'port' => $parsedPath['port'] ?? 21,
                ])
            );
        } elseif ('sftp' === $parsedPath['scheme']) {
            return new SftpAdapter(
                new SftpConnectionProvider($parsedPath['host'], $parsedPath['user'] ?? get_current_user(), $parsedPath['pass'] ?? null, null, null, $parsedPath['port'] ?? 22, true),
                $parsedPath['path'] ?? '/'
            );
        }

        throw new RuntimeException('Unable to create a filesystem adapter');
    }

    /**
     * Get all the files to import split into chunks.
     */
    private function getFilesToImport(Filesystem $filesystem, int $size): iterable
    {
        if ($size < 1) {
            throw new \InvalidArgumentException('Cannot have a "size" smaller than 1');
        }

        $files = $filesystem->listContents('', Filesystem::LIST_DEEP)->filter(function (StorageAttributes $attributes) {
            return $attributes->isFile();
        });

        $collection = new Collection();

        foreach ($files as $file) {
            $collection->add($file->path());

            if ($collection->count() >= $size) {
                yield $collection;
                $collection = new Collection();
            }
        }
    }

    /**
     * Get the signed upload request for the given environment and path.
     */
    private function getSignedUploadRequest(string $environment, Collection $files): Collection
    {
        return $this->apiClient->getSignedUploadRequests($this->projectConfiguration->getProjectId(), $environment, $files->map(function (string $filePath) {
            return ['path' => $filePath];
        })->all());
    }
}
