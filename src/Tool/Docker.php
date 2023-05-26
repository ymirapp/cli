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

namespace Ymir\Cli\Tool;

use Symfony\Component\Console\Exception\RuntimeException;

class Docker extends CommandLineTool
{
    /**
     * Build a docker image.
     */
    public static function build(string $file, string $tag, string $cwd = null)
    {
        self::runCommand(sprintf('build --pull --file=%s --tag=%s .', $file, $tag), $cwd);
    }

    /**
     * Login to a Docker registry.
     */
    public static function login(string $username, string $password, string $server, string $cwd = null)
    {
        self::runCommand(sprintf('login --username %s --password %s %s', $username, $password, $server), $cwd);
    }

    /**
     * Push a docker image.
     */
    public static function push(string $image, string $cwd = null)
    {
        self::runCommand(sprintf('push %s', $image), $cwd);
    }

    /**
     * Remove all images matching grep pattern.
     */
    public static function rmigrep(string $pattern, string $cwd = null)
    {
        try {
            self::runCommand(sprintf('rmi -f $(docker images | grep \'%s\')', $pattern), $cwd);
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
    public static function tag(string $sourceImage, string $targetImage, string $cwd = null)
    {
        self::runCommand(sprintf('tag %s %s', $sourceImage, $targetImage), $cwd);
    }

    /**
     * {@inheritdoc}
     */
    protected static function getCommand(): string
    {
        return 'docker';
    }

    /**
     * {@inheritdoc}
     */
    protected static function getName(): string
    {
        return 'Docker';
    }
}
