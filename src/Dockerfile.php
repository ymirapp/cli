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

namespace Ymir\Cli;

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

class Dockerfile
{
    /**
     * Path to the Dockerfile stub.
     *
     * @var string
     */
    private $dockerfileStubPath;

    /**
     * The file system.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * The project directory where the project files are copied from.
     *
     * @var string
     */
    private $projectDirectory;

    /**
     * Constructor.
     */
    public function __construct(Filesystem $filesystem, string $projectDirectory, string $stubDirectory)
    {
        $this->filesystem = $filesystem;
        $this->projectDirectory = rtrim($projectDirectory, '/');
        $this->dockerfileStubPath = rtrim($stubDirectory, '/').'/Dockerfile';

        if (!$this->filesystem->exists($this->dockerfileStubPath)) {
            throw new RuntimeException('Cannot find "Dockerfile" stub file');
        }
    }

    /**
     * Create a new Dockerfile.
     */
    public function create(string $environment = '')
    {
        $this->filesystem->copy($this->dockerfileStubPath, $this->getDockerfilePath($environment));
    }

    /**
     * Check if a Dockerfile exists.
     */
    public function exists(string $environment = ''): bool
    {
        return $this->filesystem->exists($this->getDockerfilePath())
            || $this->filesystem->exists($this->getDockerfilePath($environment));
    }

    /**
     * Get the path to the Dockerfile.
     */
    private function getDockerfilePath(string $environment = ''): string
    {
        $dockerfileName = 'Dockerfile';

        if (!empty($environment)) {
            $dockerfileName = $environment.'.'.$dockerfileName;
        }

        return $this->projectDirectory.'/'.$dockerfileName;
    }
}
