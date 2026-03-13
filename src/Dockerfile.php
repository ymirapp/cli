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
use Ymir\Cli\Exception\InvalidArgumentException;
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
     * Get the Dockerfile name for the given environment.
     */
    public static function getFileName(string $environment = ''): string
    {
        return empty($environment) ? 'Dockerfile' : sprintf('%s.Dockerfile', $environment);
    }

    /**
     * Create a new Dockerfile.
     */
    public function create(string $architecture, string $phpVersion, string $environment = ''): void
    {
        $this->filesystem->dumpFile(
            $this->generateDockerfilePath($environment), $this->generateDockerfileContent($this->resolvePlatform($architecture), $this->resolveRuntimeImage($architecture), $this->normalizePhpTag($phpVersion))
        );
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

        if ('arm64' === $architecture && 1 === preg_match('/ymirapp\/php-runtime/i', $fromLine)) {
            throw new SystemException('You must use the "ymirapp/arm-php-runtime" image with the "arm64" architecture');
        } elseif ('arm64' !== $architecture && 1 === preg_match('/ymirapp\/arm-php-runtime/i', $fromLine)) {
            throw new SystemException('You must use the "ymirapp/php-runtime" image with the "x86_64" architecture');
        }

        if (!preg_match('/--platform=([^\s]+)/i', $fromLine, $matches)) {
            return;
        } elseif ('arm64' === $architecture && !str_contains($matches[1], 'arm64')) {
            throw new SystemException(sprintf('The "--platform" flag in the "Dockerfile" FROM instruction must be "linux/arm64" when using the "arm64" architecture, "%s" given', $matches[1]));
        } elseif ('arm64' !== $architecture && str_contains($matches[1], 'arm64')) {
            throw new SystemException(sprintf('The "--platform" flag in the "Dockerfile" FROM instruction must be "linux/amd64" when using the "x86_64" architecture, "%s" given', $matches[1]));
        }
    }

    /**
     * Generate Dockerfile content from the Dockerfile stub.
     */
    private function generateDockerfileContent(string $platform, string $runtimeImage, string $phpTag): string
    {
        $content = file_get_contents($this->dockerfileStubPath);

        if (false === $content) {
            throw new SystemException('Unable to read "Dockerfile" stub file');
        }

        return strtr($content, [
            '__YMIR_DOCKER_PLATFORM__' => $platform,
            '__YMIR_DOCKER_RUNTIME_IMAGE__' => $runtimeImage,
            '__YMIR_DOCKER_PHP_TAG__' => $phpTag,
        ]);
    }

    /**
     * Generate the path to the Dockerfile.
     */
    private function generateDockerfilePath(string $environment = ''): string
    {
        return sprintf('%s/%s', $this->projectDirectory, self::getFileName($environment));
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

    /**
     * Normalize a PHP version into a Docker tag.
     */
    private function normalizePhpTag(string $phpVersion): string
    {
        if (empty($phpVersion)) {
            throw new InvalidArgumentException('Unable to generate Dockerfile because no PHP version was provided');
        }

        $version = '';

        if (1 === preg_match('/^php-(\d+)\.(\d+)$/', $phpVersion, $matches)) {
            $version = $matches[1].$matches[2];
        } elseif (1 === preg_match('/^php-(\d{2})$/', $phpVersion, $matches)) {
            $version = $matches[1];
        } elseif (1 === preg_match('/^(\d+)\.(\d+)$/', $phpVersion, $matches)) {
            $version = $matches[1].$matches[2];
        } elseif (1 === preg_match('/^(\d{2})$/', $phpVersion, $matches)) {
            $version = $matches[1];
        }

        if (empty($version)) {
            throw new InvalidArgumentException(sprintf('Unable to generate Dockerfile because "%s" is not a valid PHP version', $phpVersion));
        }

        return sprintf('php-%s', $version);
    }

    /**
     * Resolve the Docker platform from architecture.
     */
    private function resolvePlatform(string $architecture): string
    {
        return 'arm64' === $architecture ? 'linux/arm64' : 'linux/amd64';
    }

    /**
     * Resolve the Docker runtime image from architecture.
     */
    private function resolveRuntimeImage(string $architecture): string
    {
        return 'arm64' === $architecture ? 'ymirapp/arm-php-runtime' : 'ymirapp/php-runtime';
    }
}
