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

use Symfony\Component\Filesystem\Filesystem;
use Ymir\Cli\Exception\SystemException;

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
            throw new SystemException('Cannot find "Dockerfile" stub file');
        }
    }

    /**
     * Create a new Dockerfile.
     */
    public function create(string $environment = ''): void
    {
        $this->filesystem->copy($this->dockerfileStubPath, $this->generateDockerfilePath($environment), true);
    }

    /**
     * Check if a Dockerfile exists.
     */
    public function exists(string $environment = ''): bool
    {
        return $this->filesystem->exists($this->generateDockerfilePath())
            || $this->filesystem->exists($this->generateDockerfilePath($environment));
    }

    /**
     * Validate the Dockerfile.
     */
    public function validate(string $environment, string $architecture = ''): void
    {
        if (!$this->exists($environment)) {
            throw new SystemException('Unable to find a "Dockerfile" in the project directory');
        }

        $fromLine = (string) collect(file($this->getDockerfilePath($environment)))->first(function (string $line) {
            return 1 === preg_match('/^[\s]*FROM/i', $line);
        });

        // TODO: Validate "--platform" in FROM

        if ('arm64' === $architecture && 1 === preg_match('/ymirapp\/php-runtime/i', $fromLine)) {
            throw new SystemException('You must use the "ymirapp/arm-php-runtime" image with the "arm64" architecture');
        } elseif ('arm64' !== $architecture && 1 === preg_match('/ymirapp\/arm-php-runtime/i', $fromLine)) {
            throw new SystemException('You must use the "ymirapp/php-runtime" image with the "x86_64" architecture');
        }
    }

    /**
     * Generate the path to the Dockerfile.
     */
    private function generateDockerfilePath(string $environment = ''): string
    {
        $dockerfileName = 'Dockerfile';

        if (!empty($environment)) {
            $dockerfileName = $environment.'.'.$dockerfileName;
        }

        return $this->projectDirectory.'/'.$dockerfileName;
    }

    /**
     * Get the path to the Dockerfile.
     */
    private function getDockerfilePath(string $environment = ''): string
    {
        $dockerfilePath = $this->generateDockerfilePath();

        if ($this->filesystem->exists($this->generateDockerfilePath($environment))) {
            $dockerfilePath = $this->generateDockerfilePath($environment);
        }

        return $dockerfilePath;
    }
}
