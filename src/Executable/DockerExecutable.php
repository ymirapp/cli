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

namespace Ymir\Cli\Executable;

use Ymir\Cli\Exception\RuntimeException;

class DockerExecutable extends AbstractExecutable
{
    /**
     * Build a docker image.
     */
    public function build(string $file, string $tag, string $architecture = '', ?string $cwd = null): void
    {
        $platform = 'linux/amd64';

        if ('arm64' === $architecture) {
            $platform = 'linux/arm64';
        }

        $this->run(sprintf('build --pull --file=%s --platform=%s --tag=%s .', $file, $platform, $tag), $cwd, null);
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayName(): string
    {
        return 'Docker';
    }

    /**
     * {@inheritdoc}
     */
    public function getExecutable(): string
    {
        static $executable;

        if (!is_string($executable)) {
            $executable = $this->isExecutableInstalled('podman') ? 'podman' : 'docker';
        }

        return $executable;
    }

    /**
     * Login to a Docker registry.
     */
    public function login(string $username, string $password, string $server, ?string $cwd = null): void
    {
        $this->run(sprintf('login --username %s --password %s %s', $username, $password, $server), $cwd);
    }

    /**
     * Push a docker image.
     */
    public function push(string $image, ?string $cwd = null): void
    {
        $this->run(sprintf('push %s', $image), $cwd, null);
    }

    /**
     * Remove all images matching grep pattern.
     */
    public function removeImagesMatchingPattern(string $pattern, ?string $cwd = null): void
    {
        try {
            $this->run(sprintf('rmi -f $(docker images | grep \'%s\')', $pattern), $cwd);
        } catch (RuntimeException $exception) {
            $throwException = collect([
                '"docker rmi" requires at least 1 argument',
                'Error: No such image',
            ])->doesntContain(function (string $ignore) use ($exception) {
                return false === stripos($exception->getMessage(), $ignore);
            });

            if ($throwException) {
                throw $exception;
            }
        }
    }

    /**
     * Create a docker image tag.
     */
    public function tag(string $sourceImage, string $targetImage, ?string $cwd = null): void
    {
        $this->run(sprintf('tag %s %s', $sourceImage, $targetImage), $cwd);
    }
}
