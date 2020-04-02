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

namespace Placeholder\Cli\Build;

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

class CopyPlaceholderFilesStep implements BuildStepInterface
{
    /**
     * The build directory where the project files are copied to.
     *
     * @var string
     */
    private $buildDirectory;

    /**
     * The file system.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * The directory where the stub files are.
     *
     * @var string
     */
    private $stubDirectory;

    /**
     * Constructor.
     */
    public function __construct(string $buildDirectory, Filesystem $filesystem, string $stubDirectory)
    {
        $this->buildDirectory = rtrim($buildDirectory, '/');
        $this->filesystem = $filesystem;
        $this->stubDirectory = rtrim($stubDirectory, '/');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Copying Placeholder files';
    }

    /**
     * {@inheritdoc}
     */
    public function perform()
    {
        $configFile = 'placeholder-config.php';
        $configStubPath = $this->stubDirectory.'/'.$configFile;

        if (!$this->filesystem->exists($configStubPath)) {
            throw new RuntimeException(sprintf('No "%s" stub found', $configFile));
        }

        $this->filesystem->copy($configStubPath, $this->buildDirectory.'/'.$configFile);
    }
}
